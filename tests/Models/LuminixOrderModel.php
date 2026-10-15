<?php

namespace Luminix\Bi\Tests\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;

class LuminixOrderModel extends Model
{
    use LuminixModel;

    protected $table = 'orders';

    public function scopeAllowed(Builder $query, string $permission)
    {
        $query->where('tenant', auth()->user()->tenant);
    }

    public function customer()
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }
}
