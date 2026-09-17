<?php

namespace Luminix\Bi;

use App;
use ReflectionClass;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class DashboardResolver
{
    private $dashboards;

    public function __construct()
    {
        $this->dashboards = collect();

        $directory = app_path('Bi/Dashboards');

        // Finder::in() throws when the directory is absent. The resolver is built
        // on every boot payload, so an app that has not run `bi:install` yet would
        // fail on every request instead of only on the BI routes.
        if (!is_dir($directory)) {
            return;
        }

        $namespace = app()->getNamespace();

        foreach ((new Finder())->in($directory)->files() as $dashboard) {
            $dashboard = $namespace . str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($dashboard->getPathname(), app_path() . DIRECTORY_SEPARATOR)
            );
            if (is_subclass_of($dashboard, Dashboard::class) &&
                !(new ReflectionClass($dashboard))->isAbstract()) {
                $dashboardInstance = App::make($dashboard);
                if ($dashboardInstance->viewable()) {
                    $this->dashboards->put($dashboardInstance->uriKey, $dashboardInstance);
                }
            }
        }
    }

    public function find($dashboardUriKey)
    {
        return $this->dashboards->get($dashboardUriKey);
    }

    public function all()
    {
        return $this->dashboards;
    }

    public function first()
    {
        return $this->dashboards->first();
    }
}
