<?php

namespace Luminix\Bi\Tests\Widgets;

use Luminix\Bi\Concerns\HasCsvOutput;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\StringFilter;
use Luminix\Bi\Tests\Models\FooModel;
use Luminix\Bi\Widgets\BigNumber;
use Orchestra\Testbench\TestCase;

class DashboardTest extends TestCase
{
    private function makeDashboard(): Dashboard
    {
        return new class extends Dashboard {
            public $uriKey = 'test-dashboard';
            public $name   = 'Test Dashboard';
            public $model  = FooModel::class;

            public function widgets(): array
            {
                return [
                    BigNumber::create('count', 'Count'),
                    BigNumber::create('total', 'Total'),
                ];
            }

            public function filters(): array
            {
                return [
                    StringFilter::create('name', 'Name'),
                    StringFilter::create('status', 'Status'),
                ];
            }
        };
    }

    public function test_find_widget_or_fail_returns_matching_widget(): void
    {
        $dashboard = $this->makeDashboard();
        $widget    = $dashboard->findWidgetOrFail('count');

        $this->assertInstanceOf(BigNumber::class, $widget);
        $this->assertEquals('count', $widget->key);
    }

    public function test_find_widget_or_fail_returns_second_widget(): void
    {
        $dashboard = $this->makeDashboard();
        $widget    = $dashboard->findWidgetOrFail('total');

        $this->assertEquals('total', $widget->key);
    }

    public function test_find_widget_or_fail_aborts_with_404_for_missing_widget(): void
    {
        $dashboard = $this->makeDashboard();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $dashboard->findWidgetOrFail('nonexistent');
    }

    public function test_find_filter_or_fail_returns_matching_filter(): void
    {
        $dashboard = $this->makeDashboard();
        $filter    = $dashboard->findFilterOrFail('name');

        $this->assertInstanceOf(StringFilter::class, $filter);
        $this->assertEquals('name', $filter->key);
    }

    public function test_find_filter_or_fail_returns_second_filter(): void
    {
        $dashboard = $this->makeDashboard();
        $filter    = $dashboard->findFilterOrFail('status');

        $this->assertEquals('status', $filter->key);
    }

    public function test_find_filter_or_fail_aborts_with_404_for_missing_filter(): void
    {
        $dashboard = $this->makeDashboard();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $dashboard->findFilterOrFail('nonexistent');
    }

    public function test_viewable_returns_true_by_default(): void
    {
        $dashboard = $this->makeDashboard();

        $this->assertTrue($dashboard->viewable());
    }

    public function test_has_csv_output_returns_false_by_default(): void
    {
        $dashboard = $this->makeDashboard();

        $this->assertFalse($dashboard->hasCsvOutput());
        $this->assertFalse($dashboard->jsonSerialize()['csvEnabled']);
    }

    public function test_has_csv_output_returns_true_when_trait_is_used(): void
    {
        $dashboard = new class extends Dashboard {
            use HasCsvOutput;

            public $uriKey = 'csv-dashboard';
            public $name   = 'CSV Dashboard';
            public $model  = FooModel::class;

            public function widgets(): array { return []; }
            public function filters(): array { return []; }
        };

        $this->assertTrue($dashboard->hasCsvOutput());
        $this->assertTrue($dashboard->jsonSerialize()['csvEnabled']);
    }

    public function test_json_serialize_returns_required_keys(): void
    {
        $dashboard = $this->makeDashboard();
        $data      = $dashboard->jsonSerialize();

        $this->assertArrayHasKey('uriKey', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('csvEnabled', $data);
        $this->assertArrayHasKey('widgets', $data);
        $this->assertArrayHasKey('filters', $data);
    }

    public function test_json_serialize_includes_widgets_and_filters(): void
    {
        $dashboard = $this->makeDashboard();
        $data      = $dashboard->jsonSerialize();

        $this->assertCount(2, $data['widgets']);
        $this->assertCount(2, $data['filters']);
        $this->assertEquals('test-dashboard', $data['uriKey']);
    }
}
