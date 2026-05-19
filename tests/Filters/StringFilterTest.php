<?php

namespace Luminix\Bi\Tests\Filters;

use Luminix\Bi\Filters\StringFilter;

class StringFilterTest extends AbstractFilterTestCase
{
    public function test_apply_adds_where_in(): void
    {
        $filter  = StringFilter::create('name', 'Name');
        $builder = $filter->apply($this->baseBuilder, ['foo', 'bar'], $this->makeBiRequest());

        $this->assertSql($builder, 'select * from "foo" where "name" in (?, ?)');
    }

    public function test_apply_uses_custom_column(): void
    {
        $filter  = StringFilter::create('status', 'Status')->column('name');
        $builder = $filter->apply($this->baseBuilder, ['active'], $this->makeBiRequest());

        $this->assertSql($builder, 'select * from "foo" where "name" in (?)');
    }

    public function test_apply_with_single_value(): void
    {
        $filter  = StringFilter::create('name', 'Name');
        $builder = $filter->apply($this->baseBuilder, ['only'], $this->makeBiRequest());

        $this->assertSql($builder, 'select * from "foo" where "name" in (?)');
    }
}
