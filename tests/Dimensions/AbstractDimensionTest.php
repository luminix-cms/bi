<?php

namespace Luminix\Bi\Tests\Dimensions;

use Orchestra\Testbench\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Dimensions\Dimension;
use Luminix\Bi\Tests\Models\FooModel;

abstract class AbstractDimensionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->baseBuilder = FooModel::query();
    }

    public function test_query_builder(): void
    {
        $dimension = $this->buildDimension();
        $builder   = $dimension->apply($this->baseBuilder, BigNumber::create('test', 'Test'));
        $this->assertSql($builder, $this->getQuery());
    }

    protected function assertSql(Builder $builder, $query): void
    {
        $this->assertEquals($builder->toSql(), $query);
    }

    abstract protected function buildDimension(): Dimension;

    abstract protected function getQuery(): string;
}
