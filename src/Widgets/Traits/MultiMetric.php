<?php

namespace Luminix\Bi\Widgets\Traits;

use Luminix\Bi\Support\AttributeCollection;

trait MultiMetric
{
    protected $metrics;

    public function metrics(array $metrics): self
    {
        $this->metrics = new AttributeCollection($metrics);

        return $this;
    }
}
