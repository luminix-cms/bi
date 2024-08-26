<?php

namespace Luminix\Bi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Attribute
{
    public $key;
    public $name;
    public $column;
    public $color;

    public function __construct(string $key, string $name)
    {
        $this->key    = $key;
        $this->name   = $name;
        $this->column = $key;
    }

    public static function create(string $key, string $name): static
    {
        return new static($key, $name);
    }

    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function color($color): static
    {
        $this->color = $color;

        return $this;
    }

    public function display(Model $value, array $models)
    {
        return $value->{$this->key};
    }

    public function applySort(Builder $builder, string $dir): Builder
    {
        return $builder->orderBy($this->key, $dir);
    }
}
