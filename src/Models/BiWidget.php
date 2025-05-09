<?php

namespace Luminix\Bi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiWidget extends Model
{
    protected $table = 'bi_widgets';
    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';
    
    public function dimensions(): HasMany
    {
        return $this->hasMany(BiDimension::class, 'widget_ulid', 'ulid');
    }
    
    public function metrics(): HasMany
    {
        return $this->hasMany(BiMetric::class, 'widget_ulid', 'ulid');
    }
}
