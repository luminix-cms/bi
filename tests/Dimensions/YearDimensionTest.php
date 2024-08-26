<?php

namespace Luminix\Bi\Tests\Dimensions;

use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Dimensions\YearDimension;

class YearDimensionTest extends AbstractDimensionTest
{
    protected function buildDimension(): Dimension
    {
        return YearDimension::create('dimension', 'Dimension')->column('column');
    }

    protected function getQuery(): string
    {
        return 'select DATE_FORMAT(column, \'%Y\') as `dimension` from "foo" group by DATE_FORMAT(column, \'%Y\')';
    }
}
