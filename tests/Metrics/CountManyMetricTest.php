<?php

namespace Luminix\Bi\Tests\Metrics;

use Luminix\Bi\Metrics\CountManyMetric;
use Luminix\Bi\Metrics\Metric;
use Luminix\Bi\Widgets\BigNumber;

class CountManyMetricTest extends AbstractMetricTestCase
{
    protected function buildMetric(): Metric
    {
        return CountManyMetric::create('bar_count', 'Bar Count');
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

        $this->assertStringContainsString('"bar_count"', $sql);
        $this->assertStringContainsString('count(*)', $sql);
        $this->assertStringContainsString('group by', $sql);
    }

    public function test_relation_auto_detected_from_key_with_count_suffix(): void
    {
        $metric = new CountManyMetric('bar_count', 'Bar Count');

        $this->assertEquals('bar', $metric->relation);
    }

    public function test_relation_not_auto_detected_without_count_suffix(): void
    {
        $metric = new CountManyMetric('total', 'Total');

        $this->assertNull($metric->relation);
    }

    public function test_relation_can_be_overridden(): void
    {
        $metric = CountManyMetric::create('bar_count', 'Bar Count')->relation('items');

        $this->assertEquals('items', $metric->relation);
    }

    public function test_scope_can_be_set(): void
    {
        $scope  = function ($query) { return $query; };
        $metric = CountManyMetric::create('bar_count', 'Bar Count')->scope($scope);

        $this->assertSame($scope, $metric->scope);
    }

    public function test_query_with_scope_uses_closure(): void
    {
        $called = false;
        $scope  = function ($query) use (&$called) {
            $called = true;
            return $query;
        };

        $metric  = CountManyMetric::create('bar_count', 'Bar Count')->scope($scope);
        $builder = $metric->apply($this->baseBuilder, BigNumber::create('test', 'Test'));
        $sql     = $builder->toSql();

        $this->assertStringContainsString('"bar_count"', $sql);
    }
}
