<?php

namespace App\Domains\Reporting\Queries;

use App\Support\ModuleExtensions;
use Illuminate\Database\Eloquent\Builder;

/**
 * Which invoice rows are the company's customer-facing sales.
 *
 * With no receipt module enabled these are the standard invoices. Once one is
 * enabled the standard invoice is out of the picture and only the customer
 * receipts of the enabled modules count. Supplier-side templates (a Lorry
 * Receipt is a hire owed to the owner) are never customer figures.
 */
final class CustomerInvoiceScope
{
    public static function apply(Builder $query): Builder
    {
        if (! ModuleExtensions::receiptModulesEnabled()) {
            return $query->where(fn (Builder $q) => $q->whereNull('template_name')->orWhere('template_name', 'invoice1'));
        }

        return $query->whereIn('template_name', ModuleExtensions::salesTemplates());
    }
}
