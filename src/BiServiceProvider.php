<?php

namespace Luminix\Bi;

use App;
use Route;
use Config;
use Illuminate\Support\ServiceProvider;
use Luminix\Frontend\Services\BootService;

class BiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->registerPublishing();
        }

        $this->mergeDefaultConfig();

        $this->registerRoutes();
        $this->registerCommands();
        $this->bindResolverToContainer();
        $this->wireConfiguration();
    }

    protected function mergeDefaultConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/bi.php', 'luminix.bi');
    }

    protected function registerPublishing()
    {
        $this->publishes([
            __DIR__ . '/../stubs/service-provider.stub' => app_path('Providers/BiServiceProvider.php')
        ], 'bi-provider');

        $this->publishes([
            __DIR__ . '/../config/bi.php' => config_path('luminix/bi.php')
        ], 'bi-config');
    }

    protected function registerRoutes()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }

    protected function registerCommands()
    {
        $this->commands([
            Console\DashboardCommand::class,
            Console\InstallCommand::class
        ]);
    }

    protected function bindResolverToContainer()
    {
        $this->app->singleton(DashboardResolver::class, function () {
            return new DashboardResolver();
        });
    }

    protected function wireConfiguration()
    {
        // Reducible rebinds $this to null when invoking the reducer, so the
        // check is reached through this captured reference rather than $this.
        $provider = $this;

        BootService::reducer('wireConfig', function (array $config) use ($provider) {
            if (!$provider->userCanSeeAnyDashboard()) {
                return $config;
            }

            return array_merge_recursive($config, [
                'luminix' => [
                    'bi' => [
                        'path' => config('luminix.bi.path'),
                    ],
                ],
            ]);
        });
    }

    public function userCanSeeAnyDashboard(): bool
    {
        return $this->app->make(DashboardResolver::class)->all()->isNotEmpty();
    }

}
