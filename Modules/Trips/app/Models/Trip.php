<?php

declare(strict_types=1);

namespace Modules\Trips\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One lorry's journey: the card on the board.
 *
 * @property int $id
 * @property int $company_id
 * @property int $trip_no
 * @property int $status_id
 * @property string $board_position
 * @property string|null $from_city
 * @property string|null $to_city
 * @property string|null $pickup_date
 * @property string|null $delivered_date
 * @property string|null $goods
 * @property string|null $weight
 * @property string|null $e_way_bill
 * @property string|null $lorry_no
 * @property int|null $owner_party_id
 * @property int|null $driver_party_id
 * @property int|null $broker_party_id
 * @property string|null $notes
 * @property string|null $cancelled_at
 */
class Trip extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'trips';

    protected $fillable = [
        'company_id', 'trip_no', 'status_id', 'board_position',
        'from_city', 'to_city', 'pickup_date', 'delivered_date',
        'goods', 'weight', 'e_way_bill', 'lorry_no',
        'owner_party_id', 'driver_party_id', 'broker_party_id',
        'notes', 'cancelled_at',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'pickup_date' => 'date',
        'delivered_date' => 'date',
        'owner_party_id' => 'integer',
        'driver_party_id' => 'integer',
        'broker_party_id' => 'integer',
    ];

    /** @return BelongsTo<TripStatus, self> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(TripStatus::class, 'status_id');
    }

    /** @return HasMany<TripReceipt> */
    public function receipts(): HasMany
    {
        return $this->hasMany(TripReceipt::class);
    }

    /** @return HasMany<TripEvent> */
    public function events(): HasMany
    {
        return $this->hasMany(TripEvent::class);
    }

    /** @return HasMany<TripExpense> */
    public function expenses(): HasMany
    {
        return $this->hasMany(TripExpense::class);
    }

    /** @param Builder<self> $query */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pod');
    }
}
