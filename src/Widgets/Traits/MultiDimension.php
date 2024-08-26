<?php

namespace Luminix\Bi\Widgets\Traits;

use Luminix\Bi\Support\AttributeCollection;

trait MultiDimension
{
    protected $dimensions;

    public function dimensions(array $dimensions): self
    {
        $this->dimensions = new AttributeCollection($dimensions);

        return $this;
    }
}
