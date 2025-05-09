<?php

namespace Luminix\Bi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiDashboard extends Model
{
    protected $table = 'bi_dashboards';
    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';

    public function widgets(): HasMany
    {
        return $this->hasMany(BiWidget::class, 'dashboard_ulid', 'ulid');
    }

    public function filters(): HasMany
    {
        return $this->hasMany(BiFilter::class, 'dashboard_ulid', 'ulid');
    }
}
