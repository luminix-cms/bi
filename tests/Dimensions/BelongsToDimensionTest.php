<?php

namespace Luminix\Bi\Tests\Dimensions;

use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Dimensions\BelongsToDimension;

class BelongsToDimensionTest extends AbstractDimensionTestCase
{
    protected function buildDimension(): Dimension
    {
        return BelongsToDimension::create('bar', 'Bar')->otherColumn('name');
    }

    protected function getQuery(): string
    {
        return 'select "bar_id" from "foo" group by "bar_id"';
    }

    public function test_other_column_emits_no_dynamic_property_deprecation(): void
    {
        $deprecations = [];
        set_error_handler(function ($errno, $errstr) use (&$deprecations) {
            $deprecations[] = $errstr;

            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);

        try {
            BelongsToDimension::create('bar', 'Bar')->otherColumn('name');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
    }
}
