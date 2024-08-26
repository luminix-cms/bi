<?php

namespace Luminix\Bi\Filters;

use Luminix\Bi\Dashboard;
use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Support\BiRequest;

abstract class BaseFilter implements Filter
{
    public $key;
    public $name;
    public $column;
    public $defaultValue;

    abstract public function apply(Builder $builder, array $filterData, BiRequest $request);

    public function __construct($key, $name)
    {
        $this->key    = $key;
        $this->name   = $name;
        $this->column = $key;
    }

    public static function create(string $key, string $name): self
    {
        return new static($key, $name);
    }

    public function column(string $column): self
    {
        $this->column = $column;

        return $this;
    }

    public function defaultValue($defaultValue): self
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        return [];
    }
}
