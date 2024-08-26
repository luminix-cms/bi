<?php

namespace Luminix\Bi\Metrics;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Luminix\Bi\Widgets\Widget;

class CountManyMetric extends BaseMetric
{
    public $key;
    public $relation;
    public Closure $scope;

    public function __construct($key, $name)
    {
        parent::__construct($key, $name);

        $this->key = $key;

        if (Str::endsWith($key, '_count')) {
            $this->relation = Str::beforeLast($key, '_count');
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

    public function apply(Builder $builder, Widget $widget): Builder {

        $relations = isset($this->scope)
            ? [$this->relation . ' as ' . $this->key => $this->scope]
            : [$this->relation . ' as ' . $this->key];
        
        return $builder->withCount($relations)
            ->groupBy($builder->getModel()->getKeyName());
    }


}

