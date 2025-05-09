<?php

namespace Luminix\Bi\Widgets;

use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Metrics\Metric;
use Illuminate\Support\Collection;

class GenericWidget implements Widget
{
    public string $key;
    public string $name;
    public ?string $component = null;
    public int $width = 12;
    public ?array $extra = null;
    protected Collection $dimensions;
    protected Collection $metrics;

    public function __construct(string $key, string $name)
    {
        $this->key = $key;
        $this->name = $name;
        $this->dimensions = new Collection();
        $this->metrics = new Collection();
    }

    public static function create(string $key, string $name): self
    {
        return new self($key, $name);
    }

    public function dimensions($dimensions = null)
    {
        if ($dimensions !== null) {
            $this->dimensions = $dimensions instanceof Collection
                ? $dimensions
                : new Collection($dimensions);
            return $this;
        }

        return $this->dimensions;
    }

    public function metrics($metrics = null)
    {
        if ($metrics !== null) {
            $this->metrics = $metrics instanceof Collection
                ? $metrics
                : new Collection($metrics);
            return $this;
        }

        return $this->metrics;
    }

    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'component' => $this->component,
            'width' => $this->width,
            'extra' => $this->extra,
            'dimensions' => $this->dimensions->toArray(),
            'metrics' => $this->metrics->toArray()
        ];
    }
}
