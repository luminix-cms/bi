<?php

namespace Luminix\Bi\Tests\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Luminix\Backend\Model\LuminixModel;

class LuminixFooModel extends Model
{
    use LuminixModel;

    protected $table = 'foo';

    public function scopeAllowed(Builder $query, string $permission)
    {
        $query->whereIn('name', auth()->user()->allowed_names);
    }
}
