<?php

namespace Luminix\Bi\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrashableCustomerModel extends Model
{
    use SoftDeletes;

    protected $table = 'customers';
}
