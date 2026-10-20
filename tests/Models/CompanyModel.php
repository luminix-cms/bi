<?php

namespace Luminix\Bi\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyModel extends Model
{
    protected $table = 'companies';

    public function sector()
    {
        return $this->belongsTo(SectorModel::class, 'sector_id');
    }

    public function customers()
    {
        return $this->hasMany(CustomerModel::class, 'company_id');
    }

    public function orders()
    {
        return $this->hasManyThrough(OrderModel::class, CustomerModel::class, 'company_id', 'customer_id');
    }

    public function ordersOfCurrentCustomers()
    {
        return $this->hasManyThrough(OrderModel::class, TrashableCustomerModel::class, 'company_id', 'customer_id');
    }

    public function firstOrder()
    {
        return $this->hasOneThrough(OrderModel::class, CustomerModel::class, 'company_id', 'customer_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
