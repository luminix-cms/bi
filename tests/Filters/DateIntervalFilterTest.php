<?php

namespace Luminix\Bi\Tests\Filters;

use Carbon\Carbon;
use Luminix\Bi\Filters\DateIntervalFilter;

class DateIntervalFilterTest extends AbstractFilterTestCase
{
    public function test_apply_adds_where_between_for_date_interval(): void
    {
        $filter  = DateIntervalFilter::create('created_at', 'Date Range');
        $builder = $filter->apply(
            $this->baseBuilder,
            ['start' => '2024-01-01', 'end' => '2024-01-31'],
            $this->makeBiRequest()
        );

        $this->assertSql($builder, 'select * from "foo" where "created_at" between ? and ?');
    }

    public function test_apply_uses_custom_column(): void
    {
        $filter  = DateIntervalFilter::create('period', 'Period')->column('created_at');
        $builder = $filter->apply(
            $this->baseBuilder,
            ['start' => '2024-01-01', 'end' => '2024-01-31'],
            $this->makeBiRequest()
        );

        $this->assertSql($builder, 'select * from "foo" where "created_at" between ? and ?');
    }

    public function test_apply_includes_entire_end_day(): void
    {
        $filter  = DateIntervalFilter::create('created_at', 'Date Range');
        $builder = $filter->apply(
            $this->baseBuilder,
            ['start' => '2024-01-01', 'end' => '2024-01-31'],
            $this->makeBiRequest()
        );

        [$start, $end] = $builder->getBindings();

        $this->assertSame('2024-01-01 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2024-01-31 23:59:59', $end->format('Y-m-d H:i:s'));
    }

    public function test_default_dates_sets_default_value(): void
    {
        $filter = DateIntervalFilter::create('created_at', 'Date Range');
        $start  = Carbon::parse('2024-01-01');
        $end    = Carbon::parse('2024-01-31');
        $filter->defaultDates($start, $end);

        $this->assertEquals(
            ['start' => '2024-01-01', 'end' => '2024-01-31'],
            $filter->defaultValue
        );
    }

    public function test_default_dates_returns_self(): void
    {
        $filter = DateIntervalFilter::create('created_at', 'Date Range');
        $result = $filter->defaultDates(Carbon::parse('2024-01-01'), Carbon::parse('2024-01-31'));

        $this->assertSame($filter, $result);
    }
}
