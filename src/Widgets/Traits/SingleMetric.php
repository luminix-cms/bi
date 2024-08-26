<?php

namespace Luminix\Bi\Widgets\Traits;

use Luminix\Bi\Metrics\Metric;
use Luminix\Bi\Support\AttributeCollection;

trait SingleMetric
{
    protected $metric;
    protected $metrics;

    public function metric(Metric $metric): self
    {
        $this->metric = $metric;
        $this->metric = new AttributeCollection([$this->metric]);

        return $this;
    }
}
