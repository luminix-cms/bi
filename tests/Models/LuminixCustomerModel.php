<?php

namespace Luminix\Bi\Tests\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;

class LuminixCustomerModel extends Model
{
    use LuminixModel;

    protected $table = 'customers';

    public function scopeAllowed(Builder $query, string $permission)
    {
        $query->whereRaw('0 = 1');
    }

    public function company()
    {
        return $this->belongsTo(CompanyModel::class, 'company_id');
    }
}
