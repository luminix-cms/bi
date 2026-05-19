<?php

namespace Luminix\Bi\Tests\Widgets;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\RelationFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Support\BiRequest;
use Luminix\Bi\Tests\Models\FooModel;
use Luminix\Bi\Widgets\BigNumber;
use Orchestra\Testbench\TestCase;

class ConnectionConfigTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.connections.bi_replica', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Default connection (name varies by Testbench version — use Schema without arg)
        Schema::create('foo', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('bar', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        DB::table('foo')->insert([['id' => 1], ['id' => 2]]);
        DB::table('bar')->insert([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ]);

        // Replica connection: 3 rows in each table (different count to distinguish)
        Schema::connection('bi_replica')->create('foo', function (Blueprint $table) {
            $table->id();
        });
        Schema::connection('bi_replica')->create('bar', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        DB::connection('bi_replica')->table('foo')->insert([['id' => 1], ['id' => 2], ['id' => 3]]);
        DB::connection('bi_replica')->table('bar')->insert([
            ['id' => 1, 'name' => 'X'],
            ['id' => 2, 'name' => 'Y'],
            ['id' => 3, 'name' => 'Z'],
        ]);
    }

    private function makeDashboard(): Dashboard
    {
        return new class extends Dashboard {
            public $model = FooModel::class;
            public function widgets(): array { return []; }
            public function filters(): array { return []; }
        };
    }

    private function makeBiRequest(): BiRequest
    {
        return new BiRequest(new Request());
    }

    public function test_widget_uses_default_connection_when_not_configured(): void
    {
        config(['luminix.bi.connection' => null]);

        $result = BigNumber::create('count', 'Count')
            ->metrics([CountMetric::create('count', 'Count')])
            ->data($this->makeDashboard(), $this->makeBiRequest());

        $this->assertEquals(2, $result->first()->count);
    }

    public function test_widget_uses_configured_connection(): void
    {
        config(['luminix.bi.connection' => 'bi_replica']);

        $result = BigNumber::create('count', 'Count')
            ->metrics([CountMetric::create('count', 'Count')])
            ->data($this->makeDashboard(), $this->makeBiRequest());

        $this->assertEquals(3, $result->first()->count);
    }

    public function test_relation_filter_extra_uses_default_connection_when_not_configured(): void
    {
        config(['luminix.bi.connection' => null]);

        $extra = RelationFilter::create('bar', 'Bar')
            ->otherColumn('name')
            ->extra($this->makeDashboard(), $this->makeBiRequest());

        $this->assertCount(2, $extra['options']);
    }

    public function test_relation_filter_extra_uses_configured_connection(): void
    {
        config(['luminix.bi.connection' => 'bi_replica']);

        $extra = RelationFilter::create('bar', 'Bar')
            ->otherColumn('name')
            ->extra($this->makeDashboard(), $this->makeBiRequest());

        $this->assertCount(3, $extra['options']);
    }
}
