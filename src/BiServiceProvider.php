<?php

namespace Luminix\Bi;

use App;
use Route;
use Config;
use Illuminate\Support\ServiceProvider;
use Luminix\Frontend\Services\BootService;

class BiServiceProvider extends ServiceProvider
{
    protected static ?\Closure $configReducer = null;

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
        // BootService keeps its reducers in a static property, so a worker that
        // boots providers more than once per process would stack a new closure on
        // every boot. Dropping the previous one keeps the registry at one entry.
        if (static::$configReducer !== null) {
            BootService::removeReducer('wireConfig', static::$configReducer);
        }

        // Reducible rebinds the closure to a null $this, so the resolver is read
        // from the container at call time rather than captured from the provider.
        // That also keeps the check on the current request's container.
        static::$configReducer = function (array $config) {
            if (app(DashboardResolver::class)->all()->isEmpty()) {
                return $config;
            }

            return array_replace_recursive($config, [
                'luminix' => [
                    'bi' => [
                        'path' => config('luminix.bi.path'),
                    ],
                ],
            ]);
        };

        BootService::reducer('wireConfig', static::$configReducer);
    }

}
