<?php

namespace LaravelBi\LaravelBi\Dimensions;

use DB;
use LaravelBi\LaravelBi\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class DateDimension extends BaseDimension
{
    private $format;

    public function apply(Builder $builder, Widget $widget): Builder
    {
        return $builder->addSelect(DB::raw("DATE_FORMAT({$this->column}, '{$this->format}') as `{$this->key}`"))
                       ->groupBy($this->key);
    }

    public function format($format)
    {
        $this->format = $format;

        return $this;
    }
}
