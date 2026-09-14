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
}
