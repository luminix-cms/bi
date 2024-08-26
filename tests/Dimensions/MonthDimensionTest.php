<?php

namespace Luminix\Bi\Tests\Dimensions;

use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Dimensions\MonthDimension;

class MonthDimensionTest extends AbstractDimensionTest
{
    protected function buildDimension(): Dimension
    {
        return MonthDimension::create('dimension', 'Dimension')->column('column');
    }

    protected function getQuery(): string
    {
        return 'select DATE_FORMAT(column, \'%Y-%m\') as `dimension` from "foo" group by DATE_FORMAT(column, \'%Y-%m\')';
    }
}
