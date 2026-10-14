<?php

namespace Luminix\Bi\Tests\Filters;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Filters\StringFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Services\QueryService;
use Luminix\Bi\Tests\Models\FooModel;
use Luminix\Bi\Tests\Models\LuminixFooModel;
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

    public function test_extra_options_are_sorted_even_when_the_scope_orders_by_another_column(): void
    {
        $this->seedFooNames();

        $options = $this->extraOptions($this->makeDashboardWithScope(fn (Builder $builder) => $builder->latest('id')));

        $this->assertEquals(['Alpha', 'Beta', 'Gamma'], $options);
    }

    public function test_extra_options_accept_a_column_of_a_table_joined_by_the_scope(): void
    {
        $this->seedFooNames();
        $this->seedBars();

        $dashboard = $this->makeDashboardWithScope(fn (Builder $builder) => $builder->join('bar', 'bar.id', '=', 'foo.bar_id'));

        $options = StringFilter::create('city', 'City')->extra($dashboard, $this->makeBiRequest())['options']->all();

        $this->assertEquals(['X', 'Y'], $options);
    }

    public function test_qualified_column_disambiguates_options_and_apply_when_the_scope_joins(): void
    {
        $this->seedFooNames();
        $this->seedBars();

        $dashboard = $this->makeDashboardWithScope(fn (Builder $builder) => $builder->join('bar', 'bar.id', '=', 'foo.bar_id'));
        $filter    = StringFilter::create('name', 'Name')->column('foo.name');

        $options = $filter->extra($dashboard, $this->makeBiRequest())['options']->all();
        $matched = $filter->apply(QueryService::forDashboard($dashboard), ['Beta'], $this->makeBiRequest())->count();

        $this->assertEquals(['Alpha', 'Beta'], $options);
        $this->assertEquals(1, $matched);
    }

    public function test_extra_options_respect_allowed_of_a_luminix_model(): void
    {
        $this->seedFooNames();
        $this->actingAs(new GenericUser(['id' => 1, 'allowed_names' => ['Alpha']]));

        $options = $this->extraOptions($this->makeDashboardWithScope(fn (Builder $builder) => $builder, LuminixFooModel::class));

        $this->assertEquals(['Alpha'], $options);
    }

    public function test_or_where_in_the_scope_does_not_escape_allowed(): void
    {
        $this->seedFooNames();
        $this->actingAs(new GenericUser(['id' => 1, 'allowed_names' => ['Alpha']]));

        $dashboard = $this->makeDashboardWithScope(
            fn (Builder $builder) => $builder->where('name', 'Alpha')->orWhere('name', 'Gamma'),
            LuminixFooModel::class
        );

        $this->assertEquals(['Alpha'], $this->extraOptions($dashboard));
    }

    private function seedFooNames(): void
    {
        Schema::create('foo', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('bar_id')->nullable();
        });

        DB::table('foo')->insert([
            ['name' => 'Alpha', 'bar_id' => 1],
            ['name' => 'Beta', 'bar_id' => 2],
            ['name' => 'Alpha', 'bar_id' => 1],
            ['name' => 'Gamma', 'bar_id' => null],
        ]);
    }

    private function seedBars(): void
    {
        Schema::create('bar', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
        });

        DB::table('bar')->insert([
            ['name' => 'One', 'city' => 'X'],
            ['name' => 'Two', 'city' => 'Y'],
            ['name' => 'Three', 'city' => 'Z'],
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

    private function makeDashboardWithScope(Closure $scope, string $model = FooModel::class): Dashboard
    {
        $dashboard = new class extends Dashboard {
            public $model;
            public Closure $scopeUsing;

            public function widgets(): array { return []; }

            public function filters(): array { return []; }

            public function scope(Builder $builder): Builder
            {
                return ($this->scopeUsing)($builder);
            }
        };

        $dashboard->model      = $model;
        $dashboard->scopeUsing = $scope;

        return $dashboard;
    }
}
