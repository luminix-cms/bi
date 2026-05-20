# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About

`luminix/bi` is a Laravel package for building analytical dashboards. It is a fork of `laravel-bi/laravel-bi` adapted to work within the `luminix/backend` ecosystem. It exposes a JSON API consumed by a frontend — there is no server-rendered UI in this package.

**Requirements:** PHP 8.2+, Laravel 11+, `luminix/backend ^1.0`

## Commands

```bash
# Run all tests
docker run --rm -v $(pwd):/app -w /app php-composer:8.2 composer test

# Run with code coverage
docker run --rm -v $(pwd):/app -w /app php-composer:8.2 composer test:coverage

# Run a specific test class or method
docker run --rm -v $(pwd):/app -w /app php-composer:8.2 vendor/bin/testbench package:test --filter=WidgetFeaturesTest
docker run --rm -v $(pwd):/app -w /app php-composer:8.2 vendor/bin/testbench package:test --filter=test_table_data_returns_all_rows
```

Tests use `orchestra/testbench` — there is no full Laravel app in this repo. Tests that touch the DB create their own schema in `setUp()` using SQLite in-memory.

## Architecture

### Core concepts

The package has four composable building blocks:

| Concept | Interface/Base | Role |
|---|---|---|
| **Dashboard** | `Dashboard` (abstract class) | Root object — owns the model, widgets, and filters |
| **Widget** | `Widget` interface / `BaseWidget` | Defines what data shape to return |
| **Metric** | `Metric` interface / `BaseMetric` | SQL aggregate (`SUM`, `COUNT`, etc.) |
| **Dimension** | `Dimension` interface / `BaseDimension` | SQL `SELECT` + `GROUP BY` column |
| **Filter** | `BaseFilter` | WHERE clause applied from request data |

Both `Metric` and `Dimension` extend `Attribute`, which provides `key`, `name`, `column`, `color`, and a `display()` method for post-processing raw results.

### Query pipeline

When a widget request arrives, `BaseWidget::data()` builds the query in this order:

1. **Base builder** — `QueryService::create($dashboard->model)` applies the configured DB connection (`luminix.bi.connection`) and, if the model is a Luminix model, enforces the `allowed()` gate from `luminix/backend`.
2. **Dashboard scope** — optional `scope()` method on the Dashboard class.
3. **Widget scope** — per-widget `scope(Closure)` to narrow results further.
4. **Attributes** — each Metric and Dimension calls `apply(Builder)` on the builder (adds `SELECT` fragments and `GROUP BY`).
5. **Filters** — filters present in `request->filters()` are applied by matching `filter->key` to the request payload.
6. **Display mapping** — results are mapped through `displayModel()`, which calls `attribute->display()` on each row. `BaseMetric` supports `.asPercentage()` here.

`LineChart` overrides `data()` to fill gaps when the dimension is a `DateDimension` (Month/Day/Year), producing a continuous time series with zero values for missing periods.

### Dashboard discovery

`DashboardResolver` (singleton) scans `app/Bi/Dashboards/` in the host application at boot time, instantiates every non-abstract subclass of `Dashboard`, and keeps only those where `viewable()` returns `true`. Dashboards are keyed by their `$uriKey` property.

### API routes

Routes are prefixed with `{path}-apis` (default: `bi-apis`) and protected by the middleware defined in `config/luminix/bi.php` (default: `['web', 'auth', 'can:read-bi-reports']`).

| Method | URI | Description |
|---|---|---|
| GET | `/bi-apis/dashboards` | List all dashboards |
| GET | `/bi-apis/{dashboard}/widgets` | Dashboard metadata + widget list |
| GET | `/bi-apis/{dashboard}/widgets/{widget}` | Widget data |
| GET | `/bi-apis/{dashboard}/widgets/{widget}/csv` | CSV download |
| GET | `/bi-apis/{dashboard}/filters/{filter}` | Filter options |

### Extending the package

- **New widget**: extend `BaseWidget`, set `$component`, override `data()` if needed, use `HasAttributes` trait.
- **New metric**: extend `BaseMetric`, implement `apply(Builder)` to add a `SELECT` fragment.
- **New dimension**: extend `BaseDimension`, implement `apply(Builder)` to add `SELECT` + `GROUP BY`.
- **New filter**: extend `BaseFilter`, implement `apply(Builder, array $filterData, BiRequest $request)`.

All four base classes use a static `create(string $key, string $name)` factory and return `static` from fluent setters so calls can be chained.

### Configuration (`config/luminix/bi.php`)

| Key | Env var | Default | Purpose |
|---|---|---|---|
| `path` | `LUMINIX_BI_PATH` | `bi` | URI prefix |
| `middleware` | — | `['web','auth','can:read-bi-reports']` | Route middleware |
| `connection` | `BI_DB_CONNECTION` | `null` | Read replica / alternate DB connection |
| `debug` | `LUMINIX_BI_DEBUG` | `false` | Appends SQL query log to API responses |
