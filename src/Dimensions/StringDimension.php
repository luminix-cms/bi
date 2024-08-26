<?php

namespace Luminix\Bi\Dimensions;

use Luminix\Bi\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class StringDimension extends BaseDimension
{
    public function apply(Builder $builder, Widget $widget): Builder
    {
        return $builder->addSelect("{$this->column} as {$this->key}")
                       ->groupBy($this->key);
    }
}
