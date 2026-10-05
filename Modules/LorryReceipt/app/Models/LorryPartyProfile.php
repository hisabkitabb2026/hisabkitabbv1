<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Models;

use App\Domains\Accounts\Models\Company;
use App\Domains\Contacts\Models\Customer;
use App\Domains\Purchases\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Lorry Party Profile — master data for owners, drivers, and brokers.
 */
class LorryPartyProfile extends Model
{
    protected $table = 'tr_lorry_party_profiles';

    protected $guarded = ['id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The Supplier this party is on the purchasing side: the owner, driver or
     * broker the company hires vehicles from and owes money to.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Inline company scope (modules cannot use the host's HasCompanyScopes trait).
     */
    public function scopeWhereCompany($query)
    {
        $query->where($this->qualifyColumn('company_id'), request()->header('company'));
    }

    /**
     * A page of the requested size, or the whole set for the sentinel limit
     * "all" — the same pattern the host models use (Item, Payment, ...).
     * Without this, paginate('all') crashes with a TypeError.
     *
     * @return Collection|LengthAwarePaginator
     */
    public function scopePaginateData($query, $limit)
    {
        return $limit == 'all' ? $query->get() : $query->paginate($limit);
    }
}
