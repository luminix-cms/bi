# Widgets

A widget is one query and one visualization. It carries metrics and dimensions, optionally narrows
the dashboard's query further, and declares the `component` the frontend should render.

**Neighbours:** what a metric or dimension adds to the query -> `metrics-and-dimensions.md` · the
order everything is applied in -> `query-pipeline.md` · request params -> `api.md`.

| Class (`Luminix\Bi\Widgets\*`) | `component` | Specific to it |
|---|---|---|
| `BigNumber` | `big-number` | nothing — a metric with no dimension gives the single value |
| `LineChart` | `line-chart` | fills gaps in a date series, below |
| `PartitionPie` | `partition-pie` | `->colors([...])`, published in `extra` |
| `Table` | `table` | `->orderBy($col, $dir)` and request sorting, below |

```php
use Luminix\Bi\Widgets\LineChart;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Dimensions\MonthDimension;

LineChart::create('revenue-by-month', 'Revenue by Month')
    ->metric(SumMetric::create('revenue', 'Revenue')->column('total'))
    ->dimension(MonthDimension::create('created_at', 'Month'))
    ->width('2/3')                                  // opaque layout hint, passed through as-is
    ->scope(fn ($b) => $b->where('type', 'sale'));  // narrows this widget only
```

`->metric()` and `->dimension()` are the singular forms of `->metrics([...])` / `->dimensions([...])`
and **replace** the whole collection — two `->metric()` calls keep only the last one.

The `->scope()` closure is called with `$this` rebound to the widget, receives the Builder and must
return it.

## What the frontend receives

`width`, `key`, `name`, `component`, `metrics`, `dimensions` and `extra`. `extra()` defaults to
`['uniqid' => uniqid()]`; `PartitionPie` and `Table` replace it wholesale with their own payload, so
neither publishes a `uniqid`.

Data rows are objects keyed by attribute key, metrics first and dimensions after — the order
`->metrics()`/`->dimensions()` were declared in.

## `Table` sorting

`->orderBy('amount', 'desc')` only reaches `extra.orderBy` — it is the frontend's default, not a
server-side `ORDER BY`. The query is sorted only when the request carries `sort[col]` and
`sort[dir]`: the column is matched against dimension keys first, then metric keys, and an unknown
column is ignored. Sorting then goes through the attribute's `applySort()`, which orders by the
select alias (`BelongsToDimension` overrides it to order by the foreign key).

## `LineChart` gap filling

When the widget's **first** dimension is a `DateDimension` (`DayDimension`, `MonthDimension`,
`YearDimension` or your own), periods with no rows are inserted with every metric at its empty value
(`0`), stepped by that dimension's carbon interval.

The range comes from the request filter whose key equals the dimension's key, and only when that
payload carries both `start` and `end` — i.e. a `DateIntervalFilter`. Anything else (a `DateFilter`,
a filter under a different key, no filter) falls back to the minimum and maximum date present in the
result. An empty result is returned untouched: gap filling never invents a series from nothing.
