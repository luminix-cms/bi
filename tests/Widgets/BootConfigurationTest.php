<?php

namespace Luminix\Bi\Tests\Widgets;

use Luminix\Bi\BiServiceProvider;
use Luminix\Bi\DashboardResolver;
use Luminix\Frontend\Services\BootService;
use Orchestra\Testbench\TestCase;

class BootConfigurationTest extends TestCase
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

    private function fakeResolver(bool $hasViewableDashboard): DashboardResolver
    {
        $resolver = $this->createMock(DashboardResolver::class);
        $resolver->method('all')->willReturn(
            $hasViewableDashboard ? collect(['dashboard']) : collect()
        );

        return $resolver;
    }

    public function test_authorized_user_receives_configured_path_in_boot_payload(): void
    {
        config(['luminix.bi.path' => 'relatorios']);
        $this->app->instance(DashboardResolver::class, $this->fakeResolver(true));

        $config = BootService::wireConfig([]);

        $this->assertEquals('relatorios', $config['luminix']['bi']['path']);
    }

    public function test_user_without_any_viewable_dashboard_does_not_receive_the_key(): void
    {
        config(['luminix.bi.path' => 'relatorios']);
        $this->app->instance(DashboardResolver::class, $this->fakeResolver(false));

        $config = BootService::wireConfig([]);

        $this->assertArrayNotHasKey('luminix', $config);
    }

    public function test_default_path_is_published_without_env_override(): void
    {
        $this->app->instance(DashboardResolver::class, $this->fakeResolver(true));

        $config = BootService::wireConfig([]);

        $this->assertEquals('bi', $config['luminix']['bi']['path']);
    }

    /**
     * The resolver is no longer reached only from the BI routes: it now runs on
     * every boot payload, including for apps that never ran `bi:install`.
     */
    public function test_missing_dashboards_directory_does_not_break_the_boot_payload(): void
    {
        $this->assertDirectoryDoesNotExist(app_path('Bi/Dashboards'));

        $config = BootService::wireConfig(['app' => ['name' => 'Laravel']]);

        $this->assertArrayNotHasKey('luminix', $config);
        $this->assertSame(['app' => ['name' => 'Laravel']], $config);
    }

    public function test_empty_dashboards_directory_does_not_publish_the_key(): void
    {
        $directory = app_path('Bi/Dashboards');
        mkdir($directory, 0777, true);

        try {
            $config = BootService::wireConfig([]);
        } finally {
            rmdir($directory);
            rmdir(dirname($directory));
        }

        $this->assertArrayNotHasKey('luminix', $config);
    }

    public function test_path_already_present_in_the_payload_is_replaced_not_appended(): void
    {
        config(['luminix.bi.path' => 'relatorios']);
        $this->app->instance(DashboardResolver::class, $this->fakeResolver(true));

        $config = BootService::wireConfig([
            'luminix' => [
                'admin' => ['path' => 'admin'],
                'bi'    => ['path' => 'obsoleto'],
            ],
        ]);

        $this->assertSame('relatorios', $config['luminix']['bi']['path']);
        $this->assertSame('admin', $config['luminix']['admin']['path']);
    }

    public function test_booting_the_provider_twice_does_not_stack_reducers(): void
    {
        config(['luminix.bi.path' => 'relatorios']);
        $this->app->instance(DashboardResolver::class, $this->fakeResolver(true));

        (new BiServiceProvider($this->app))->boot();
        $this->app->instance(DashboardResolver::class, $this->fakeResolver(true));

        $this->assertCount(1, BootService::getReducer('wireConfig'));
        $this->assertSame('relatorios', BootService::wireConfig([])['luminix']['bi']['path']);
    }
}
