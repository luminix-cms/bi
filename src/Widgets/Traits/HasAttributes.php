<?php

namespace Luminix\Bi\Widgets\Traits;

use Luminix\Bi\Metrics\Metric;
use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Support\AttributeCollection;

trait HasAttributes
{
    protected $dimensions;
    protected $metrics;

    public function dimensions(array $dimensions): static
    {
        $this->dimensions = new AttributeCollection($dimensions);

        return $this;
    }

    public function dimension(Dimension $dimension): static
    {
        return $this->dimensions([$dimension]);
    }

    public function metrics(array $metrics): static
    {
        $this->metrics = new AttributeCollection($metrics);

        return $this;
    }

    public function metric(Metric $metric): static
    {
        return $this->metrics([$metric]);
    }

    protected function getAttributes()
    {
        return (new AttributeCollection($this->metrics))->merge($this->dimensions);
    }
}
