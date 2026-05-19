<?php

namespace Luminix\Bi\Tests\Filters;

use Luminix\Bi\Filters\NumberFilter;

class NumberFilterTest extends AbstractFilterTestCase
{
    public function test_apply_returns_unchanged_builder_when_operator_is_empty(): void
    {
        $filter  = NumberFilter::create('amount', 'Amount');
        $builder = $filter->apply($this->baseBuilder, ['operator' => ''], $this->makeBiRequest());

        $this->assertSql($builder, 'select * from "foo"');
    }

    public function test_apply_returns_unchanged_builder_when_operator_is_missing(): void
    {
        $filter  = NumberFilter::create('amount', 'Amount');
        $builder = $filter->apply($this->baseBuilder, [], $this->makeBiRequest());

        $this->assertSql($builder, 'select * from "foo"');
    }

    public function test_apply_adds_where_between_for_between_operator(): void
    {
        $filter  = NumberFilter::create('amount', 'Amount');
        $builder = $filter->apply(
            $this->baseBuilder,
            ['operator' => 'between', 'values' => [10, 20]],
            $this->makeBiRequest()
        );

        $this->assertSql($builder, 'select * from "foo" where "amount" between ? and ?');
    }

    public function test_apply_adds_where_for_gte_operator(): void
    {
        $filter  = NumberFilter::create('amount', 'Amount');
        $builder = $filter->apply(
            $this->baseBuilder,
            ['operator' => '>=', 'values' => [10]],
            $this->makeBiRequest()
        );

        $this->assertSql($builder, 'select * from "foo" where "amount" >= ?');
    }

    public function test_apply_adds_where_for_lte_operator(): void
    {
        $filter  = NumberFilter::create('amount', 'Amount');
        $builder = $filter->apply(
            $this->baseBuilder,
            ['operator' => '<=', 'values' => [100]],
            $this->makeBiRequest()
        );

        $this->assertSql($builder, 'select * from "foo" where "amount" <= ?');
    }

    public function test_apply_adds_where_for_equality_operator(): void
    {
        $filter  = NumberFilter::create('amount', 'Amount');
        $builder = $filter->apply(
            $this->baseBuilder,
            ['operator' => '=', 'values' => [42]],
            $this->makeBiRequest()
        );

        $this->assertSql($builder, 'select * from "foo" where "amount" = ?');
    }
}
