<?php

declare(strict_types=1);

namespace Modules\LrReceipt\Http\Controllers;

use App\Domains\Contacts\Models\Customer;
use App\Domains\Sales\Models\Invoice;
use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\LrReceipt\Support\Abilities;
use Modules\LrReceipt\Support\Authorizes;
use Modules\LrReceipt\Support\CompanyContext;

/**
 * AI-powered auto-fill for LR Receipts using Gemini OCR.
 * Upload a photo/PDF of a physical LR, extract consignor, consignee, goods, route.
 */
class LrReceiptAutoFillController extends Controller
{
    public function __construct(private readonly Authorizes $moduleAuthorizes) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::VIEW_LR_RECEIPT);

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $apiKey = config('services.gemini.key');
        if (! $apiKey) {
            return response()->json([
                'error' => 'Gemini API Key is not configured. Please add GEMINI_API_KEY to your .env file.',
            ], 422);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

        $mimeMap = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];

        if (! isset($mimeMap[$extension])) {
            return response()->json([
                'error' => 'Unsupported file type. Please upload a PDF or image (JPEG/PNG).',
            ], 422);
        }

        $mimeType = $mimeMap[$extension];
        $base64Data = base64_encode(file_get_contents($file->getRealPath()));

        $schema = $this->buildSchema();
        $prompt = 'Extract all transport receipt fields from this document. Return the data as JSON matching the provided schema. For any field not found, return an empty string.';

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64Data]],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $schema,
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Gemini API error', ['response' => $response->body()]);

                return response()->json([
                    'error' => 'Failed to process the document with AI. Please try again.',
                ], 422);
            }

            $body = $response->json();
            $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $extracted = json_decode($text, true) ?? [];

            $consignor = $this->findCustomer($extracted['consignor_name'] ?? '', $extracted['consignor_city'] ?? '');
            $consignee = $this->findCustomer($extracted['consignee_name'] ?? '', $extracted['consignee_city'] ?? '');

            if ($consignor) {
                $extracted['consignor_customer_id'] = $consignor->id;
            }
            if ($consignee) {
                $extracted['consignee_customer_id'] = $consignee->id;
            }

            return response()->json(['data' => $extracted]);
        } catch (\Exception $e) {
            Log::error('Gemini auto-fill error', ['message' => $e->getMessage()]);

            return response()->json([
                'error' => 'An error occurred while processing the document: '.$e->getMessage(),
            ], 422);
        }
    }

    private function buildSchema(): array
    {
        $fields = [
            'consignor_name', 'consignor_gstin', 'consignor_phone', 'consignor_address', 'consignor_city',
            'consignee_name', 'consignee_gstin', 'consignee_phone', 'consignee_address', 'consignee_city',
            'from', 'to', 'truck_no', 'mode_of_payment', 'gst_payable_by',
            'description_of_goods', 'hsn_code', 'eway_bill_no',
            'actual_weight', 'charged_weight', 'no_of_articles', 'packing',
            'basic_freight', 'hamali', 'fov', 'local_collection', 'door_delivery', 'docket_charge', 'other_charge',
        ];

        $properties = [];
        foreach ($fields as $field) {
            $properties[$field] = ['type' => 'STRING'];
        }

        return ['type' => 'OBJECT', 'properties' => $properties];
    }

    private function findCustomer(string $name, ?string $city = null): ?Customer
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $query = Customer::whereCompany()
            ->where(function ($q) use ($name) {
                $q->where('name', $name)
                    ->orWhereRaw('LOWER(name) = ?', [strtolower($name)]);
            });

        if (! empty($city)) {
            $customer = (clone $query)->whereHas('billingAddress', function ($q) use ($city) {
                $q->whereRaw('LOWER(city) = ?', [strtolower(trim($city))]);
            })->first();

            if ($customer) {
                return $customer->load(['billingAddress', 'shippingAddress']);
            }
        }

        return $query->first()?->load(['billingAddress', 'shippingAddress']);
    }
}
