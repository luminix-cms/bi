<?php

namespace Luminix\Bi\Models;

use Illuminate\Database\Eloquent\Model;

class BiMetric extends Model
{
    protected $table = 'bi_metrics';
    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';
}
