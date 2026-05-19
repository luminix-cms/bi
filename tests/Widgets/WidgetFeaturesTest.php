<?php

namespace Luminix\Bi\Tests\Widgets;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Filters\StringFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Support\AttributeCollection;
use Luminix\Bi\Support\BiRequest;
use Luminix\Bi\Tests\Models\FooModel;
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Widgets\LineChart;
use Luminix\Bi\Widgets\PartitionPie;
use Luminix\Bi\Widgets\Table;
use Orchestra\Testbench\TestCase;

class WidgetFeaturesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('foo', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });

        DB::table('foo')->insert([
            ['id' => 1, 'name' => 'Alpha', 'amount' => 100],
            ['id' => 2, 'name' => 'Beta',  'amount' => 200],
            ['id' => 3, 'name' => 'Alpha', 'amount' => 50],
        ]);
    }

    private function makeDashboard(?array $filters = []): Dashboard
    {
        $filterList = $filters;

        return new class($filterList) extends Dashboard {
            public $model = FooModel::class;
            private array $filterList;

            public function __construct(array $filterList)
            {
                $this->filterList = $filterList;
            }

            public function widgets(): array { return []; }

            public function filters(): array { return $this->filterList; }
        };
    }

    private function makeBiRequest(array $input = []): BiRequest
    {
        return new BiRequest(new Request($input));
    }

    // -------------------------------------------------------------------------
    // Table widget
    // -------------------------------------------------------------------------

    public function test_table_data_returns_all_rows(): void
    {
        $widget = Table::create('table', 'Table')
            ->dimensions([StringDimension::create('name', 'Name')])
            ->metrics([CountMetric::create('count', 'Count')]);

        $result = $widget->data($this->makeDashboard(), $this->makeBiRequest());

        $this->assertCount(2, $result); // Alpha and Beta (grouped by name)
    }

    public function test_table_orderby_sets_properties(): void
    {
        $widget = Table::create('table', 'Table')->orderBy('name', 'desc');

        $this->assertEquals('name', $widget->orderByColumn);
        $this->assertEquals('desc', $widget->orderByDir);
    }

    public function test_table_extra_contains_order_by(): void
    {
        $widget = Table::create('table', 'Table')->orderBy('amount', 'asc');
        $extra  = $widget->jsonSerialize()['extra'];

        $this->assertEquals('amount', $extra['orderBy']['col']);
        $this->assertEquals('asc', $extra['orderBy']['dir']);
    }

    public function test_table_data_with_sort_by_dimension(): void
    {
        $widget = Table::create('table', 'Table')
            ->dimensions([StringDimension::create('name', 'Name')])
            ->metrics([CountMetric::create('count', 'Count')]);

        $request = $this->makeBiRequest(['sort' => ['col' => 'name', 'dir' => 'asc']]);
        $result  = $widget->data($this->makeDashboard(), $request);

        $this->assertNotEmpty($result);
    }

    public function test_table_data_with_sort_by_metric(): void
    {
        $widget = Table::create('table', 'Table')
            ->dimensions([StringDimension::create('name', 'Name')])
            ->metrics([CountMetric::create('count', 'Count')]);

        $request = $this->makeBiRequest(['sort' => ['col' => 'count', 'dir' => 'desc']]);
        $result  = $widget->data($this->makeDashboard(), $request);

        $this->assertNotEmpty($result);
    }

    public function test_table_data_with_sort_by_unknown_column(): void
    {
        $widget = Table::create('table', 'Table')
            ->dimensions([StringDimension::create('name', 'Name')])
            ->metrics([CountMetric::create('count', 'Count')]);

        $request = $this->makeBiRequest(['sort' => ['col' => 'unknown', 'dir' => 'asc']]);
        $result  = $widget->data($this->makeDashboard(), $request);

        $this->assertNotEmpty($result);
    }

    // -------------------------------------------------------------------------
    // PartitionPie widget
    // -------------------------------------------------------------------------

    public function test_partition_pie_extra_contains_colors(): void
    {
        $widget = PartitionPie::create('pie', 'Pie')->colors(['red', 'blue', 'green']);
        $extra  = $widget->jsonSerialize()['extra'];

        $this->assertEquals(['red', 'blue', 'green'], $extra['colors']);
    }

    public function test_partition_pie_without_colors_returns_null(): void
    {
        $widget = PartitionPie::create('pie', 'Pie');
        $extra  = $widget->jsonSerialize()['extra'];

        $this->assertNull($extra['colors']);
    }

    public function test_partition_pie_colors_returns_self(): void
    {
        $widget = PartitionPie::create('pie', 'Pie');
        $result = $widget->colors(['red']);

        $this->assertSame($widget, $result);
    }

    // -------------------------------------------------------------------------
    // LineChart widget
    // -------------------------------------------------------------------------

    public function test_line_chart_data_with_string_dimension(): void
    {
        $widget = LineChart::create('chart', 'Chart')
            ->dimensions([StringDimension::create('name', 'Name')])
            ->metrics([CountMetric::create('count', 'Count')]);

        $result = $widget->data($this->makeDashboard(), $this->makeBiRequest());

        $this->assertCount(2, $result);
    }

    // -------------------------------------------------------------------------
    // Widget traits: single dimension/metric
    // -------------------------------------------------------------------------

    public function test_widget_dimension_singular_sets_single_dimension(): void
    {
        $dimension  = StringDimension::create('name', 'Name');
        $widget     = BigNumber::create('w', 'W')->dimension($dimension);
        $serialized = $widget->jsonSerialize();

        $this->assertCount(1, $serialized['dimensions']);
        $this->assertSame($dimension, $serialized['dimensions']->first());
    }

    public function test_widget_metric_singular_sets_single_metric(): void
    {
        $metric     = CountMetric::create('count', 'Count');
        $widget     = BigNumber::create('w', 'W')->metric($metric);
        $serialized = $widget->jsonSerialize();

        $this->assertCount(1, $serialized['metrics']);
        $this->assertSame($metric, $serialized['metrics']->first());
    }

    public function test_widget_dimensions_plural_sets_multiple_dimensions(): void
    {
        $d1         = StringDimension::create('name', 'Name');
        $d2         = StringDimension::create('status', 'Status');
        $widget     = BigNumber::create('w', 'W')->dimensions([$d1, $d2]);
        $serialized = $widget->jsonSerialize();

        $this->assertCount(2, $serialized['dimensions']);
    }

    public function test_widget_metrics_plural_sets_multiple_metrics(): void
    {
        $m1         = CountMetric::create('count', 'Count');
        $m2         = SumMetric::create('total', 'Total');
        $widget     = BigNumber::create('w', 'W')->metrics([$m1, $m2]);
        $serialized = $widget->jsonSerialize();

        $this->assertCount(2, $serialized['metrics']);
    }

    public function test_widget_width_can_be_set(): void
    {
        $widget = BigNumber::create('w', 'W')->width(6);

        $this->assertEquals(6, $widget->width);
        $this->assertEquals(6, $widget->jsonSerialize()['width']);
    }

    // -------------------------------------------------------------------------
    // AttributeCollection
    // -------------------------------------------------------------------------

    public function test_attribute_collection_get_by_key_returns_matching_attribute(): void
    {
        $metric     = CountMetric::create('count', 'Count');
        $collection = new AttributeCollection([$metric]);

        $found = $collection->getByKey('count');

        $this->assertSame($metric, $found);
    }

    public function test_attribute_collection_get_by_key_returns_null_for_missing_key(): void
    {
        $collection = new AttributeCollection([CountMetric::create('count', 'Count')]);

        $this->assertNull($collection->getByKey('nonexistent'));
    }

    // -------------------------------------------------------------------------
    // BaseMetric: asPercentage
    // -------------------------------------------------------------------------

    public function test_count_metric_as_percentage_formats_output(): void
    {
        $widget = BigNumber::create('count', 'Count')
            ->metrics([CountMetric::create('count', 'Count')->asPercentage()]);

        $result = $widget->data($this->makeDashboard(), $this->makeBiRequest());

        // All 3 rows = 3 total; result has one row (no groupBy) with count=3 = 100%
        $this->assertStringEndsWith('%', $result->first()->count);
    }

    // -------------------------------------------------------------------------
    // Filters applied through widget
    // -------------------------------------------------------------------------

    public function test_widget_applies_string_filter_from_request(): void
    {
        $filter  = StringFilter::create('name', 'Name');
        $widget  = BigNumber::create('count', 'Count')
            ->metrics([CountMetric::create('count', 'Count')]);

        $request = $this->makeBiRequest(['filters' => ['name' => ['Alpha']]]);
        $result  = $widget->data($this->makeDashboard([$filter]), $request);

        $this->assertEquals(2, $result->first()->count);
    }

    public function test_widget_ignores_filters_not_present_in_request(): void
    {
        $filter = StringFilter::create('name', 'Name');
        $widget = BigNumber::create('count', 'Count')
            ->metrics([CountMetric::create('count', 'Count')]);

        $result = $widget->data($this->makeDashboard([$filter]), $this->makeBiRequest());

        $this->assertEquals(3, $result->first()->count);
    }

    // -------------------------------------------------------------------------
    // Widget scope
    // -------------------------------------------------------------------------

    public function test_widget_scope_restricts_query(): void
    {
        $widget = BigNumber::create('count', 'Count')
            ->scope(function ($builder) {
                return $builder->where('name', 'Beta');
            })
            ->metrics([CountMetric::create('count', 'Count')]);

        $result = $widget->data($this->makeDashboard(), $this->makeBiRequest());

        $this->assertEquals(1, $result->first()->count);
    }

    // -------------------------------------------------------------------------
    // BaseFilter: column and defaultValue
    // -------------------------------------------------------------------------

    public function test_base_filter_column_can_be_changed(): void
    {
        $filter = StringFilter::create('name', 'Name')->column('status');

        $this->assertEquals('status', $filter->column);
    }

    public function test_base_filter_default_value_can_be_set(): void
    {
        $filter = StringFilter::create('name', 'Name')->defaultValue(['active']);

        $this->assertEquals(['active'], $filter->defaultValue);
    }

    public function test_base_filter_extra_returns_empty_array_by_default(): void
    {
        $filter = DateIntervalFilter::create('created_at', 'Date');
        $extra  = $filter->extra($this->makeDashboard(), $this->makeBiRequest());

        $this->assertIsArray($extra);
        $this->assertEmpty($extra);
    }
}
