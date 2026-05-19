<?php

namespace Luminix\Bi\Tests\Filters;

use Carbon\Carbon;
use Luminix\Bi\Filters\DateFilter;

class DateFilterTest extends AbstractFilterTestCase
{
    public function test_apply_adds_where_between_for_date(): void
    {
        $filter  = DateFilter::create('created_at', 'Date');
        $builder = $filter->apply($this->baseBuilder, ['2024-01-15'], $this->makeBiRequest());

        $this->assertSql($builder, 'select * from "foo" where "created_at" between ? and ?');
    }

    public function test_apply_uses_custom_column(): void
    {
        $filter  = DateFilter::create('date', 'Date')->column('created_at');
        $builder = $filter->apply($this->baseBuilder, ['2024-01-15'], $this->makeBiRequest());

        $this->assertSql($builder, 'select * from "foo" where "created_at" between ? and ?');
    }

    public function test_default_date_sets_default_value(): void
    {
        $filter = DateFilter::create('created_at', 'Date');
        $date   = Carbon::parse('2024-01-15');
        $filter->defaultDate($date);

        $this->assertEquals(['2024-01-15'], $filter->defaultValue);
    }

    public function test_default_date_returns_self(): void
    {
        $filter = DateFilter::create('created_at', 'Date');
        $result = $filter->defaultDate(Carbon::parse('2024-01-15'));

        $this->assertSame($filter, $result);
    }
}
