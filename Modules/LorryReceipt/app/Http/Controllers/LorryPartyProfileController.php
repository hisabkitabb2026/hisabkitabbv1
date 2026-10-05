<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Http\Controllers;

use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\LorryReceipt\Application\LorryReceiptPayablesService;
use Modules\LorryReceipt\Http\Requests\LorryPartyProfileRequest;
use Modules\LorryReceipt\Http\Resources\LorryPartyProfileResource;
use Modules\LorryReceipt\Models\LorryPartyProfile;
use Modules\LorryReceipt\Support\Abilities;
use Modules\LorryReceipt\Support\Authorizes;
use Modules\LorryReceipt\Support\CompanyContext;

/**
 * Full CRUD for Lorry Party Profiles (owner/driver/broker master data).
 */
class LorryPartyProfileController extends Controller
{
    public function __construct(private readonly Authorizes $moduleAuthorizes) {}

    /**
     * A page of profiles (or all of them for limit=all). Returns a resource
     * collection, not a JsonResponse — the previous JsonResponse return type
     * made every call fail with a TypeError once paginate() was fixed.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        // Reading parties is part of filling in a receipt; managing them is not.
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::VIEW_LORRY_RECEIPT);

        $limit = $request->input('limit', 10);

        $profiles = LorryPartyProfile::whereCompany()
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('search'), function ($q, $search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            })
            ->latest()
            ->paginateData($limit);

        return LorryPartyProfileResource::collection($profiles);
    }

    public function store(LorryPartyProfileRequest $request): JsonResponse
    {
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::MANAGE_PARTY_PROFILES);

        $data = $request->validated();
        $data['company_id'] = $request->header('company');

        // A supplier picked from the host's Suppliers list links to the
        // profile; without one a new Supplier is created behind the party.
        $supplierId = $data['supplier_id'] ?? null;
        $email = $data['email'] ?? null;
        unset($data['supplier_id'], $data['email']);

        $profile = LorryPartyProfile::create($data);

        if ($supplierId !== null) {
            $this->payables()->linkSupplier($profile, (int) $supplierId);
        } else {
            $this->payables()->ensureSupplier($profile, $email);
        }

        return response()->json([
            'data' => new LorryPartyProfileResource($profile->refresh()),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::VIEW_LORRY_RECEIPT);

        $profile = LorryPartyProfile::whereCompany()->findOrFail($id);

        return response()->json([
            'data' => new LorryPartyProfileResource($profile),
        ]);
    }

    public function update(LorryPartyProfileRequest $request, int $id): JsonResponse
    {
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::MANAGE_PARTY_PROFILES);

        $profile = LorryPartyProfile::whereCompany()->findOrFail($id);
        $profile->update($request->validated());

        // Keep the Supplier behind the party in step with the new details.
        $this->payables()->ensureSupplier($profile);

        return response()->json([
            'data' => new LorryPartyProfileResource($profile->refresh()),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::MANAGE_PARTY_PROFILES);

        $profile = LorryPartyProfile::whereCompany()->findOrFail($id);
        $profile->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * The service that keeps the Supplier behind a party in step.
     */
    private function payables(): LorryReceiptPayablesService
    {
        return app(LorryReceiptPayablesService::class);
    }
}
