<?php

namespace Luminix\Bi\Tests\Filters;

use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Filters\StringFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Tests\Models\FooModel;
use Luminix\Bi\Widgets\Table;

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

    public function test_extra_options_respect_dashboard_scope_for_limited_user(): void
    {
        $this->seedFooNames();
        $this->actingAs(new GenericUser(['id' => 1, 'allowed_names' => ['Alpha']]));

        $options = $this->extraOptions($this->makeScopedDashboard());

        $this->assertEquals(['Alpha'], $options);
    }

    public function test_extra_options_without_dashboard_scope_return_all_distinct_values(): void
    {
        $this->seedFooNames();

        $options = $this->extraOptions($this->makeDashboard());

        sort($options);
        $this->assertEquals(['Alpha', 'Beta', 'Gamma'], $options);
    }

    public function test_extra_options_match_widget_rows_for_same_user(): void
    {
        $this->seedFooNames();
        $this->actingAs(new GenericUser(['id' => 1, 'allowed_names' => ['Alpha', 'Gamma']]));

        $dashboard = $this->makeScopedDashboard();
        $widget    = Table::create('table', 'Table')
            ->dimensions([StringDimension::create('name', 'Name')])
            ->metrics([CountMetric::create('count', 'Count')]);

        $rowNames = $widget->data($dashboard, $this->makeBiRequest())->pluck('name')->sort()->values()->all();
        $options  = $this->extraOptions($dashboard);

        sort($options);
        $this->assertEquals($rowNames, $options);
    }

    private function seedFooNames(): void
    {
        Schema::create('foo', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });

        DB::table('foo')->insert([
            ['name' => 'Alpha'],
            ['name' => 'Beta'],
            ['name' => 'Alpha'],
            ['name' => 'Gamma'],
        ]);
    }

    private function extraOptions(Dashboard $dashboard): array
    {
        return StringFilter::create('name', 'Name')
            ->extra($dashboard, $this->makeBiRequest())['options']
            ->all();
    }

    private function makeDashboard(): Dashboard
    {
        return new class extends Dashboard {
            public $model = FooModel::class;

            public function widgets(): array { return []; }

            public function filters(): array { return []; }
        };
    }

    private function makeScopedDashboard(): Dashboard
    {
        return new class extends Dashboard {
            public $model = FooModel::class;

            public function widgets(): array { return []; }

            public function filters(): array { return []; }

            public function scope(Builder $builder): Builder
            {
                return $builder->whereIn('foo.name', auth()->user()->allowed_names);
            }
        };
    }
}
