<?php

namespace Luminix\Bi\Tests\Filters;

use Luminix\Bi\Filters\BelongsToFilter;

class BelongsToFilterTest extends AbstractFilterTestCase
{
    public function test_apply_adds_where_in_on_foreign_key(): void
    {
        $filter  = BelongsToFilter::create('bar', 'Bar');
        $builder = $filter->apply($this->baseBuilder, [1, 2], $this->makeBiRequest());

        $this->assertSql($builder, 'select * from "foo" where "bar_id" in (?, ?)');
    }

    public function test_relation_defaults_to_key(): void
    {
        $filter = BelongsToFilter::create('bar', 'Bar');

        $this->assertEquals('bar', $filter->relation);
    }

    public function test_relation_can_be_overridden(): void
    {
        $filter = BelongsToFilter::create('bar', 'Bar');
        $result = $filter->relation('bar');

        $this->assertEquals('bar', $filter->relation);
        $this->assertSame($filter, $result);
    }

    public function test_other_column_can_be_set(): void
    {
        $filter = BelongsToFilter::create('bar', 'Bar');
        $result = $filter->otherColumn('name');

        $this->assertEquals('name', $filter->otherColumn);
        $this->assertSame($filter, $result);
    }

    public function test_column_defaults_to_key(): void
    {
        $filter = BelongsToFilter::create('bar', 'Bar');

        $this->assertEquals('bar', $filter->column);
    }
}
