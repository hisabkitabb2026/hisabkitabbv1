<?php

declare(strict_types=1);

namespace Modules\Trips\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A running cost on a trip: diesel, toll, driver, repair, other.
 *
 * @property int $id
 * @property int $company_id
 * @property int $trip_id
 * @property string $category
 * @property int $amount
 * @property string|null $note
 * @property string $expense_date
 */
class TripExpense extends Model
{
    public const CATEGORIES = ['diesel', 'toll', 'driver', 'repair', 'other'];

    protected $table = 'trip_expenses';

    protected $fillable = ['company_id', 'trip_id', 'category', 'amount', 'note', 'expense_date', 'user_id'];

    protected $casts = ['amount' => 'integer', 'expense_date' => 'date', 'payment_date' => 'date'];

    /** @return BelongsTo<Trip, self> */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
