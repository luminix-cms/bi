<?php

namespace Luminix\Bi\Tests\Routes;

use Illuminate\Support\Facades\Route;
use Luminix\Bi\BiServiceProvider;
use Luminix\Frontend\Services\BootService;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RouteNamesTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [BiServiceProvider::class];
    }

    protected function tearDown(): void
    {
        BootService::flushReducers();

        parent::tearDown();
    }

    public function test_the_route_collection_compiles_for_route_cache(): void
    {
        // `artisan route:cache` serializes this compilation, which refuses two routes sharing a name
        $compiled = Route::getRoutes()->compile();

        $this->assertNotEmpty($compiled['attributes']);
    }

    public static function apiRoutes(): array
    {
        return [
            ['bi.api.dashboards', [], '/bi-apis/dashboards'],
            ['bi.api.dashboard.widgets', ['dashboard' => 'sales'], '/bi-apis/sales/widgets'],
            ['bi.api.dashboard.widgets.widget', ['dashboard' => 'sales', 'widget' => 'total'], '/bi-apis/sales/widgets/total'],
            ['bi.api.dashboard.widgets.widget.csv', ['dashboard' => 'sales', 'widget' => 'total'], '/bi-apis/sales/widgets/total/csv'],
            ['bi.api.dashboard.filters.filter', ['dashboard' => 'sales', 'filter' => 'status'], '/bi-apis/sales/filters/status'],
        ];
    }

    #[DataProvider('apiRoutes')]
    public function test_each_api_route_is_reachable_by_its_name(string $name, array $parameters, string $path): void
    {
        $this->assertSame($path, route($name, $parameters, false));
    }
}
