<?php

namespace Luminix\Bi\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    protected $table = 'orders';

    public function customer()
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }

    public function guardedCustomer()
    {
        return $this->belongsTo(LuminixCustomerModel::class, 'customer_id');
    }
}
