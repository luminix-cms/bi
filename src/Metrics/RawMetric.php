<?php

namespace Luminix\Bi\Metrics;

use DB;
use Luminix\Bi\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class RawMetric extends BaseMetric
{
    private $raw;

    public function raw($raw)
    {
        $this->raw = $raw;

        return $this;
    }

    public function apply(Builder $builder, Widget $widget): Builder
    {
        return $builder->addSelect(DB::raw("{$this->raw} as `{$this->key}`"));
    }
}
