<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $table = 'tr_banks';

    protected $guarded = ['id'];

    public function scopeWhereCompany(Builder $query): Builder
    {
        return $query->where('company_id', request()->header('company'));
    }
}
