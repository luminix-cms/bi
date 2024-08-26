<?php

namespace Luminix\Bi\Tests\Dimensions;

use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Dimensions\StringDimension;

class StringDimensionTest extends AbstractDimensionTest
{
    protected function buildDimension(): Dimension
    {
        return StringDimension::create('dimension', 'Dimension')->column('column');
    }

    protected function getQuery(): string
    {
        return 'select "column" as "dimension" from "foo" group by "dimension"';
    }
}
