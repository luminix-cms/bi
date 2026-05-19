<?php

namespace Luminix\Bi\Tests\Metrics;

use Luminix\Bi\Metrics\SumManyMetric;
use Luminix\Bi\Metrics\Metric;
use Luminix\Bi\Widgets\BigNumber;

class SumManyMetricTest extends AbstractMetricTestCase
{
    protected function buildMetric(): Metric
    {
        return SumManyMetric::create('bar_sum_amount', 'Bar Sum Amount');
    }

    protected function getQuery(): string
    {
        return '';
    }

    public function test_query_builder(): void
    {
        $metric  = $this->buildMetric();
        $builder = $metric->apply($this->baseBuilder, BigNumber::create('test', 'Test'));
        $sql     = $builder->toSql();

        $this->assertStringContainsString('"bar_sum_amount"', $sql);
        $this->assertStringContainsString('sum', $sql);
        $this->assertStringContainsString('group by', $sql);
    }

    public function test_relation_and_column_auto_detected_from_key(): void
    {
        $metric = new SumManyMetric('bar_sum_amount', 'Bar Sum Amount');

        $this->assertEquals('bar', $metric->relation);
        $this->assertEquals('amount', $metric->column);
    }

    public function test_relation_not_auto_detected_without_sum_pattern(): void
    {
        $metric = new SumManyMetric('total', 'Total');

        $this->assertNull($metric->relation);
    }

    public function test_relation_can_be_overridden(): void
    {
        $metric = SumManyMetric::create('bar_sum_amount', 'Sum')->relation('orders');

        $this->assertEquals('orders', $metric->relation);
    }

    public function test_scope_can_be_set(): void
    {
        $scope  = function ($query) { return $query; };
        $metric = SumManyMetric::create('bar_sum_amount', 'Sum')->scope($scope);

        $this->assertSame($scope, $metric->scope);
    }

    public function test_query_with_scope_uses_closure(): void
    {
        $scope = function ($query) { return $query; };

        $metric  = SumManyMetric::create('bar_sum_amount', 'Bar Sum Amount')->scope($scope);
        $builder = $metric->apply($this->baseBuilder, BigNumber::create('test', 'Test'));
        $sql     = $builder->toSql();

        $this->assertStringContainsString('"bar_sum_amount"', $sql);
    }
}
