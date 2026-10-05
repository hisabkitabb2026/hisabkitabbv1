<?php

declare(strict_types=1);

namespace Modules\Trips\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A board column. `code` is the stable machine name the module computes with;
 * `name` is what the user reads and may rename.
 *
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property string|null $colour
 * @property int $position
 * @property bool $is_default
 * @property bool $is_closed
 */
class TripStatus extends Model
{
    protected $table = 'trip_statuses';

    protected $fillable = ['company_id', 'code', 'name', 'colour', 'position', 'is_default', 'is_closed'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_closed' => 'boolean',
    ];

    /** @param Builder<self> $query */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
