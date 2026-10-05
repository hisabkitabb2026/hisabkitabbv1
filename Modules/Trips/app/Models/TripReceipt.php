<?php

declare(strict_types=1);

namespace Modules\Trips\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document linked to a trip: an LR receipt (revenue) or a Lorry receipt
 * (cost). The amount is denormalized integer minor units.
 *
 * @property int $id
 * @property int $company_id
 * @property int $trip_id
 * @property int $invoice_id
 * @property string $type
 * @property int $amount
 * @property string|null $invoice_number
 * @property int|null $customer_id
 * @property string|null $customer_name
 * @property string|null $template_name
 * @property int|null $billed_invoice_id
 * @property string|null $billed_at
 */
class TripReceipt extends Model
{
    public const TYPE_LR = 'lr';

    public const TYPE_LORRY = 'lorry';

    public const TYPE_INVOICE = 'invoice';

    protected $table = 'trip_receipts';

    protected $fillable = [
        'company_id', 'trip_id', 'invoice_id', 'type', 'amount',
        'invoice_number', 'customer_id', 'customer_name', 'template_name',
        'billed_invoice_id', 'billed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'customer_id' => 'integer',
        'billed_invoice_id' => 'integer',
        'billed_at' => 'datetime',
    ];

    /** @return BelongsTo<Trip, self> */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
