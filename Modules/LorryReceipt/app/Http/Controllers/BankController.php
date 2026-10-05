<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Http\Controllers;

use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\LorryReceipt\Models\Bank;
use Modules\LorryReceipt\Support\Abilities;
use Modules\LorryReceipt\Support\Authorizes;
use Modules\LorryReceipt\Support\CompanyContext;

class BankController extends Controller
{
    public function __construct(private readonly Authorizes $moduleAuthorizes) {}

    public function index(Request $request): JsonResponse
    {
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::VIEW_LORRY_RECEIPT);

        $banks = Bank::whereCompany()
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $banks]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::MANAGE_PARTY_PROFILES);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $bank = Bank::firstOrCreate(
            [
                'company_id' => (int) $request->header('company'),
                'name' => $validated['name'],
            ],
            $validated
        );

        return response()->json(['data' => $bank], 201);
    }

    public function destroy(Request $request, Bank $bank): JsonResponse
    {
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::MANAGE_PARTY_PROFILES);

        $bank->delete();

        return response()->json(null, 204);
    }
}
