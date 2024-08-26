<?php

namespace Luminix\Bi\Metrics;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Luminix\Bi\Widgets\Widget;

class SumManyMetric extends BaseMetric
{

    public $key;
    public $relation;
    public Closure $scope;

    public function __construct($key, $name)
    {
        parent::__construct($key, $name);

        $this->key = $key;

        // Check if $key matches with `{relation}_sum_{column}` format
        if (Str::contains($key, '_sum_')) {
            [$relation, $column] = explode('_sum_', $key);
            if (!empty($relation) && !empty($column)){
                $this->relation = $relation;
                $this->column = $column;
            }
        }
    }

    public function relation($relation): self
    {
        $this->relation = $relation;
        return $this;
    }

    public function scope(Closure $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    public function apply(Builder $builder, Widget $widget): Builder
    {
        $relations = isset($this->scope)
            ? [$this->relation . ' as ' . $this->key => $this->scope]
            : [$this->relation . ' as ' . $this->key];

        return $builder->withSum($relations, $this->column)
            ->groupBy($builder->getModel()->getKeyName());

    }

}
