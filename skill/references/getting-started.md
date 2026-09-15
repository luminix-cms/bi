# Getting started

```bash
composer require luminix/bi
php artisan bi:install
```

`bi:install` publishes `config/luminix/bi.php`, publishes `app/Providers/BiServiceProvider.php` —
whose only job is `Gate::define('read-bi-reports')` — and writes an example
`app/Bi/Dashboards/UserDashboard.php`.

It then tries to register that provider by rewriting `config/app.php`, which Laravel 11+ no longer
uses to list providers: add `App\Providers\BiServiceProvider::class` to `bootstrap/providers.php`
yourself. Until the gate exists the default route middleware (`can:read-bi-reports`) denies every BI
endpoint -> `configuration.md`.

## A dashboard

```bash
php artisan bi:dashboard SalesDashboard --model=Order
```

Writes the class into `app/Bi/Dashboards/`, points `$model` at `App\Models\Order` and defaults
`$uriKey` to the camelCase class name (`salesDashboard`) — that is a URL segment, so usually worth
shortening.

```php
namespace App\Bi\Dashboards;

use App\Models\Order;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Concerns\HasCsvOutput;

class SalesDashboard extends Dashboard
{
    use HasCsvOutput;              // optional -> enables the CSV endpoint

    public $model  = Order::class;
    public $uriKey = 'sales';      // URL segment, and the resolver's key
    public $name   = 'Sales';

    public function widgets() { return [ /* Widget[] */ ]; }
    public function filters() { return [ /* BaseFilter[] */ ]; }

    // optional — hides the dashboard from this user entirely
    public function viewable(): bool
    {
        return auth()->user()?->can('view-sales') ?? false;
    }

    // optional — base scope applied before every widget's own scope
    public function scope($builder)
    {
        return $builder->where('status', 'paid');
    }
}
```

`widgets()` and `filters()` are abstract: implement both, even as empty arrays. The abstract class
declares none of the three properties, so the subclass owns them.

## Discovery

`DashboardResolver` is a container singleton. On first resolution it scans `app/Bi/Dashboards/`
recursively, instantiates through the container every non-abstract `Dashboard` subclass it finds,
keeps only those whose `viewable()` returns `true`, and keys them by `$uriKey`.

Consequences worth knowing before debugging a missing dashboard:

- the class must live under `app/Bi/Dashboards/` and its file path must match its namespace — the
  resolver derives the class name from the path, it does not read the file
- `viewable()` runs once per request, at resolution time, with the authenticated user available.
  A dashboard it rejects is absent from the collection, so its URL answers `404`, not `403`
- two dashboards sharing a `$uriKey` collide silently; the last one scanned wins
- an app with no `app/Bi/Dashboards/` directory resolves an empty collection instead of failing
