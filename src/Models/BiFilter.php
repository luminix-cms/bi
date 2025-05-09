<?php

namespace Luminix\Bi\Models;

use Illuminate\Database\Eloquent\Model;

class BiFilter extends Model
{
    protected $table = 'bi_filters';
    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';
}
