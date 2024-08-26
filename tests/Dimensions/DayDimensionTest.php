<?php

namespace Luminix\Bi\Tests\Dimensions;

use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Dimensions\DayDimension;

class DayDimensionTest extends AbstractDimensionTest
{
    protected function buildDimension(): Dimension
    {
        return DayDimension::create('dimension', 'Dimension')->column('column');
    }

    protected function getQuery(): string
    {
        return 'select DATE_FORMAT(column, \'%Y-%m-%d\') as `dimension` from `foo` group by DATE_FORMAT(column, \'%Y-%m-%d\')';
    }
}
