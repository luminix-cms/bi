<?php

namespace Luminix\Bi\Widgets\Traits;

use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Support\AttributeCollection;

trait SingleDimension
{
    protected $dimension;
    protected $dimensions;

    public function dimension(Dimension $dimension): self
    {
        $this->dimension  = $dimension;
        $this->dimensions = new AttributeCollection([$this->dimension]);

        return $this;
    }
}
