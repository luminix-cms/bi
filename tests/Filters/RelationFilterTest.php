<?php

namespace Luminix\Bi\Tests\Filters;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\RelationFilter;
use Luminix\Bi\Services\QueryService;
use Luminix\Bi\Tests\Models\CompanyModel;
use Luminix\Bi\Tests\Models\CustomerModel;
use Luminix\Bi\Tests\Models\LuminixOrderModel;
use Luminix\Bi\Tests\Models\NoteModel;
use Luminix\Bi\Tests\Models\OrderModel;

class RelationFilterTest extends AbstractFilterTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchema();
        $this->seedData();
    }

    public function test_options_of_a_direct_belongs_to_respect_the_dashboard_scope(): void
    {
        $this->actingAs(new GenericUser(['id' => 1, 'tenant' => 'a']));

        $options = $this->names(RelationFilter::create('customer', 'Customer'), $this->tenantDashboard());

        $this->assertEquals(['Ana'], $options);
    }

    public function test_options_of_a_nested_path_respect_the_dashboard_scope(): void
    {
        $this->actingAs(new GenericUser(['id' => 1, 'tenant' => 'b']));

        $filter = RelationFilter::create('sector', 'Sector')->relation('customer.company.sector');

        $this->assertEquals(['Industry', 'Logistics'], $this->names($filter, $this->tenantDashboard()));
    }

    public function test_a_record_no_dashboard_row_references_is_left_out(): void
    {
        $options = $this->names(RelationFilter::create('customer', 'Customer'), $this->dashboard());

        $this->assertEquals(['Ana', 'Bruno', 'Carla'], $options);
    }

    public function test_all_options_lists_every_related_record(): void
    {
        $this->actingAs(new GenericUser(['id' => 1, 'tenant' => 'a']));

        $filter = RelationFilter::create('customer', 'Customer')->allOptions();

        $this->assertEquals(['Ana', 'Bruno', 'Carla', 'Davi', 'Edu'], $this->names($filter, $this->tenantDashboard()));
    }

    public function test_allowed_of_a_luminix_dashboard_model_narrows_the_options_without_a_scope(): void
    {
        $this->actingAs(new GenericUser(['id' => 1, 'tenant' => 'a']));

        $options = $this->names(RelationFilter::create('customer', 'Customer'), $this->dashboard(LuminixOrderModel::class));

        $this->assertEquals(['Ana'], $options);
    }

    public function test_options_are_exactly_the_records_apply_can_match(): void
    {
        $this->actingAs(new GenericUser(['id' => 1, 'tenant' => 'b']));

        $dashboard = $this->tenantDashboard();
        $filter    = RelationFilter::create('customer', 'Customer');
        $offered   = collect($filter->extra($dashboard, $this->makeBiRequest())['options'])->pluck('id')->all();

        foreach (CustomerModel::all() as $customer) {
            $matched = $filter->apply(QueryService::forDashboard($dashboard), [$customer->id], $this->makeBiRequest())->count();

            $this->assertSame(in_array($customer->id, $offered), $matched > 0, "customer {$customer->name}");
        }
    }

    public function test_options_of_a_has_many_follow_the_dashboard_rows(): void
    {
        $dashboard = $this->dashboard(CompanyModel::class, fn (Builder $builder) => $builder->where('companies.name', 'Acme'));

        $this->assertEquals(['Ana', 'Edu'], $this->names(RelationFilter::create('customers', 'Customer'), $dashboard));
    }

    public function test_options_of_a_belongs_to_many_follow_the_dashboard_rows(): void
    {
        $dashboard = $this->dashboard(CustomerModel::class, fn (Builder $builder) => $builder->where('customers.name', 'Ana'));

        $this->assertEquals(['new', 'vip'], $this->names(RelationFilter::create('tags', 'Tag'), $dashboard));
    }

    public function test_options_of_a_morph_many_keep_to_the_morph_type(): void
    {
        $dashboard = $this->dashboard(CustomerModel::class, fn (Builder $builder) => $builder->where('customers.name', 'Ana'));

        $this->assertEquals(['call Ana'], $this->names(RelationFilter::create('notes', 'Note'), $dashboard));
    }

    public function test_or_where_in_the_filter_scope_does_not_escape_the_narrowing(): void
    {
        $this->actingAs(new GenericUser(['id' => 1, 'tenant' => 'a']));

        // tenant "a" reaches only Ana; Bruno and Davi are outside its rows
        $filter = RelationFilter::create('customer', 'Customer')
            ->scope(fn (Builder $builder) => $builder->where('name', 'Bruno')->orWhere('name', 'Davi'));

        $this->assertEquals([], $this->names($filter, $this->tenantDashboard()));
    }

    public function test_or_where_in_the_filter_scope_does_not_widen_the_selection(): void
    {
        // Bruno is inside the scope but outside the selection: his order must not come back
        $filter = RelationFilter::create('customer', 'Customer')
            ->scope(fn (Builder $builder) => $builder->where('name', 'Bruno')->orWhere('name', 'Ana'));

        $orders = $filter->apply(OrderModel::query(), [1], $this->makeBiRequest())->pluck('id')->sort()->values()->all();

        $this->assertSame([1, 2], $orders);
    }

    public function test_filter_scope_can_still_order_the_options(): void
    {
        $filter = RelationFilter::create('customer', 'Customer')
            ->scope(fn (Builder $builder) => $builder->orderBy('name', 'desc'));

        $this->assertEquals(['Carla', 'Bruno', 'Ana'], $this->names($filter, $this->dashboard(), sort: false));
    }

    public function test_allowed_is_not_applied_to_the_models_the_path_crosses(): void
    {
        $this->actingAs(new GenericUser(['id' => 1, 'tenant' => 'a']));

        $filter = RelationFilter::create('sector', 'Sector')->relation('guardedCustomer.company.sector');

        $this->assertEquals(['Retail'], $this->names($filter, $this->tenantDashboard()));
    }

    public function test_options_follow_the_dashboard_scope_when_the_scope_orders_by_another_column(): void
    {
        $dashboard = $this->dashboard(OrderModel::class, fn (Builder $builder) => $builder->latest('orders.id'));

        $this->assertEquals(['Ana', 'Bruno', 'Carla'], $this->names(RelationFilter::create('customer', 'Customer'), $dashboard));
    }

    public function test_a_relation_that_cannot_be_followed_asks_for_all_options(): void
    {
        $dashboard = $this->dashboard(CompanyModel::class);
        $filter    = RelationFilter::create('orders', 'Order');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('->allOptions()');

        $filter->extra($dashboard, $this->makeBiRequest());
    }

    public function test_a_morph_to_cannot_be_followed_either(): void
    {
        $dashboard = $this->dashboard(NoteModel::class);
        $filter    = RelationFilter::create('noteable', 'Noteable');

        $this->expectException(LogicException::class);

        $filter->extra($dashboard, $this->makeBiRequest());
    }

    public function test_all_options_still_works_for_a_relation_that_cannot_be_followed(): void
    {
        $dashboard = $this->dashboard(CompanyModel::class);
        $filter    = RelationFilter::create('orders', 'Order')->otherColumn('tenant')->allOptions();

        $this->assertCount(5, $filter->extra($dashboard, $this->makeBiRequest())['options']);
    }

    public function test_the_narrowing_flag_is_not_published_to_the_frontend(): void
    {
        $published = get_object_vars(RelationFilter::create('customer', 'Customer')->allOptions());

        $this->assertArrayNotHasKey('narrowOptions', $published);
    }

    private function names(RelationFilter $filter, Dashboard $dashboard, bool $sort = true): array
    {
        $filter->otherColumn($filter->otherColumn ?? 'name');

        $names = collect($filter->extra($dashboard, $this->makeBiRequest())['options'])
            ->pluck($filter->otherColumn)
            ->all();

        if ($sort) {
            sort($names);
        }

        return $names;
    }

    private function dashboard(string $model = OrderModel::class, ?Closure $scope = null): Dashboard
    {
        $dashboard = new class extends Dashboard {
            public $model;
            public ?Closure $scopeUsing = null;

            public function widgets(): array { return []; }

            public function filters(): array { return []; }

            public function scope(Builder $builder): Builder
            {
                return $this->scopeUsing ? ($this->scopeUsing)($builder) : $builder;
            }
        };

        $dashboard->model      = $model;
        $dashboard->scopeUsing = $scope;

        return $dashboard;
    }

    private function tenantDashboard(): Dashboard
    {
        return $this->dashboard(OrderModel::class, fn (Builder $builder) => $builder->where('orders.tenant', auth()->user()->tenant));
    }

    private function createSchema(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('sector_id')->nullable();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('company_id')->nullable();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('tenant');
            $table->unsignedBigInteger('customer_id')->nullable();
        });
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('customer_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('tag_id');
        });
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('noteable_id');
            $table->string('noteable_type');
        });
    }

    private function seedData(): void
    {
        DB::table('sectors')->insert([
            ['name' => 'Retail'], ['name' => 'Industry'], ['name' => 'Logistics'], ['name' => 'Unused'],
        ]);
        DB::table('companies')->insert([
            ['name' => 'Acme', 'sector_id' => 1], ['name' => 'Globex', 'sector_id' => 2], ['name' => 'Initech', 'sector_id' => 3],
        ]);
        DB::table('customers')->insert([
            ['name' => 'Ana', 'company_id' => 1],
            ['name' => 'Bruno', 'company_id' => 2],
            ['name' => 'Carla', 'company_id' => 3],
            ['name' => 'Davi', 'company_id' => null],
            ['name' => 'Edu', 'company_id' => 1],
        ]);
        DB::table('orders')->insert([
            ['tenant' => 'a', 'customer_id' => 1],
            ['tenant' => 'a', 'customer_id' => 1],
            ['tenant' => 'b', 'customer_id' => 2],
            ['tenant' => 'b', 'customer_id' => 3],
            ['tenant' => 'a', 'customer_id' => null],
        ]);
        DB::table('tags')->insert([['name' => 'vip'], ['name' => 'new'], ['name' => 'old'], ['name' => 'unused']]);
        DB::table('customer_tag')->insert([
            ['customer_id' => 1, 'tag_id' => 1],
            ['customer_id' => 1, 'tag_id' => 2],
            ['customer_id' => 2, 'tag_id' => 2],
            ['customer_id' => 3, 'tag_id' => 3],
        ]);
        DB::table('notes')->insert([
            ['name' => 'call Ana', 'noteable_id' => 1, 'noteable_type' => CustomerModel::class],
            ['name' => 'call Bruno', 'noteable_id' => 2, 'noteable_type' => CustomerModel::class],
            ['name' => 'other type', 'noteable_id' => 1, 'noteable_type' => 'other'],
        ]);
    }
}
