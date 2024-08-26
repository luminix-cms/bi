<?php

namespace Luminix\Bi\Tests\Metrics;

use Orchestra\Testbench\TestCase;
use Luminix\Bi\Metrics\Metric;
use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Tests\Models\FooModel;

abstract class AbstractMetricTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->baseBuilder = FooModel::query();
    }

    public function test_query_builder(): void
    {
        $metric  = $this->buildMetric();
        $builder = $metric->apply($this->baseBuilder, BigNumber::create('test', 'Test'));
        $this->assertSql($builder, $this->getQuery());
    }

    protected function assertSql(Builder $builder, $query): void
    {
        $this->assertEquals($builder->toSql(), $query);
    }

    abstract protected function buildMetric(): Metric;

    abstract protected function getQuery(): string;
}
