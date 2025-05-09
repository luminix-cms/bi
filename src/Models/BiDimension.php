<?php

namespace Luminix\Bi\Models;

use Illuminate\Database\Eloquent\Model;

class BiDimension extends Model
{
    protected $table = 'bi_dimensions';
    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';
}
