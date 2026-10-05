<?php

declare(strict_types=1);

namespace Modules\Trips\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of the trip's timeline.
 *
 * @property int $id
 * @property int $company_id
 * @property int $trip_id
 * @property int|null $user_id
 * @property string $type
 * @property string|null $message
 * @property string|null $created_at
 */
class TripEvent extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'trip_events';

    protected $fillable = ['company_id', 'trip_id', 'user_id', 'type', 'message', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    /** @return BelongsTo<Trip, self> */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
