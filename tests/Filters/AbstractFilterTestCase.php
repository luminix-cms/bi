<?php

namespace Luminix\Bi\Tests\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Luminix\Bi\Support\BiRequest;
use Luminix\Bi\Tests\Models\FooModel;
use Orchestra\Testbench\TestCase;

abstract class AbstractFilterTestCase extends TestCase
{
    protected Builder $baseBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseBuilder = FooModel::query();
    }

    protected function makeBiRequest(array $input = []): BiRequest
    {
        return new BiRequest(new Request($input));
    }

    protected function assertSql(Builder $builder, string $query): void
    {
        $this->assertEquals($query, $builder->toSql());
    }
}
