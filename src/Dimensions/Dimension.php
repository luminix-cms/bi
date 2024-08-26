<?php

namespace Luminix\Bi\Dimensions;

use Illuminate\Database\Eloquent\Model;
use Luminix\Bi\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

interface Dimension
{
    public function apply(Builder $builder, Widget $widget): Builder;

    public function display(Model $value, array $models);
}
