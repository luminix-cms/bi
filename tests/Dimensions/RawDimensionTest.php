<?php

namespace Luminix\Bi\Tests\Dimensions;

use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Dimensions\RawDimension;

class RawDimensionTest extends AbstractDimensionTest
{
    protected function buildDimension(): Dimension
    {
        return RawDimension::create('dimension', 'Dimension')->raw('CONCAT(firstname, \' \', lastname)');
    }

    protected function getQuery(): string
    {
        return 'select CONCAT(firstname, \' \', lastname) as `dimension` from "foo" group by "dimension"';
    }
}
