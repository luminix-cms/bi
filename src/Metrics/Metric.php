<?php

namespace Luminix\Bi\Metrics;

use Illuminate\Database\Eloquent\Model;
use Luminix\Bi\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

interface Metric
{
    public function apply(Builder $builder, Widget $widget): Builder;

    public function display(Model $value, array $models);

    public function getEmptyValue();
}
