<?php

namespace Luminix\Bi\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class SectorModel extends Model
{
    protected $table = 'sectors';

    public function customer()
    {
        return $this->hasOneThrough(CustomerModel::class, CompanyModel::class, 'sector_id', 'company_id');
    }
}
