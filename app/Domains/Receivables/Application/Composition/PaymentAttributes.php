<?php

namespace App\Domains\Receivables\Application\Composition;

use App\Domains\Accounts\Models\CompanySetting;
use App\Domains\Contacts\Models\Customer;
use App\Support\MoneyConversion;
use Illuminate\Support\Arr;

/**
 * The stored attributes of a payment, built from its validated submission.
 * The write request and the server-side composer both come through here.
 */
final class PaymentAttributes
{
    /**
     * A payment is always denominated in the customer's currency: at home the
     * rate is 1, abroad it is the submitted one, and the base amount is the
     * converted total rounded to whole minor units.
     *
     * @param  array<string, mixed>  $validated  the validated submission; allocations and custom fields are stored apart
     * @return array<string, mixed>
     */
    public static function fromInput(array $validated, int|string|null $companyId, int $creatorId): array
    {
        $currencyId = Customer::find($validated['customer_id'] ?? null)->currency_id;
        $homeCurrency = CompanySetting::getSetting('currency', $companyId);
        $rate = (string) $homeCurrency !== (string) $currencyId ? (float) ($validated['exchange_rate'] ?? null) : 1;

        $tdsAmount = (int) ($validated['tds_amount'] ?? 0);

        return array_merge(Arr::except($validated, ['allocations', 'customFields']), [
            'creator_id' => $creatorId,
            'company_id' => $companyId,
            'exchange_rate' => $rate,
            'amount' => (int) ($validated['amount'] ?? 0),
            'base_amount' => MoneyConversion::toBaseMinor(($validated['amount'] ?? 0), $rate),
            'tds_amount' => $tdsAmount,
            'base_tds_amount' => $tdsAmount > 0 ? MoneyConversion::toBaseMinor($tdsAmount, $rate) : 0,
            'currency_id' => $currencyId,
        ]);
    }
}
