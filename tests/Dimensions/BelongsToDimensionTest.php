<?php

namespace Luminix\Bi\Tests\Dimensions;

use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Dimensions\BelongsToDimension;

class BelongsToDimensionTest extends AbstractDimensionTest
{
    protected function buildDimension(): Dimension
    {
        return BelongsToDimension::create('bar', 'Bar')->otherColumn('name');
    }

    protected function getQuery(): string
    {
        return 'select `bar_id` from `foo` group by `bar_id`';
    }
}
