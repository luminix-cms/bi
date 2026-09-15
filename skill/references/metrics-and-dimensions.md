# Metrics and dimensions

A metric is the aggregate in the `SELECT`; a dimension is the column you group by. Both extend
`Attribute`, so both are built the same way and both can post-process the value before it leaves.

**Neighbours:** how they are attached to a widget -> `widgets.md` · when `apply()` runs relative to
scopes and filters -> `query-pipeline.md` · writing your own -> `extending.md`.

```php
SumMetric::create('revenue', 'Revenue')->column('total')->color('#4caf50')
```

`create($key, $name)` is the only constructor path worth using; `column` defaults to `key`, so
`->column()` is needed only when the alias and the database column differ. `key` is the alias in the
SQL, the key in the JSON row and the name the `sort[col]` param matches. `color` is passed through
untouched for the frontend.

## Metrics — `Luminix\Bi\Metrics`

| Class | Adds | Notes |
|---|---|---|
| `CountMetric` | `COUNT(*) as key` | ignores `column` |
| `SumMetric` | `SUM(column) as key` | |
| `AverageMetric` | `AVG(column) as key` | |
| `RawMetric` | `->raw('SUM(qty * price)') as key` | the expression is interpolated as written |
| `SumManyMetric` | `withSum(relation as key, column)` | also groups by the model's primary key |
| `CountManyMetric` | `withCount(relation as key)` | also groups by the model's primary key |

The four simple metrics alias with backticks, as does every date dimension — MySQL/MariaDB quoting.

`SumManyMetric` and `CountManyMetric` read the relation out of the key when it is shaped
`{relation}_sum_{column}` or `{relation}_count`; otherwise set it with `->relation('orders')`
(plus `->column('total')` for the sum). `->scope(fn ($q) => ...)` constrains the aggregated subquery.
Because they group by the primary key, they produce one row per parent record — do not combine them
on the same widget with a dimension that groups differently.

```php
SumManyMetric::create('orders_sum_total', 'Revenue')->scope(fn ($q) => $q->where('paid', true)),
CountMetric::create('orders', 'Orders')->asPercentage(),
```

`->asPercentage()` rewrites the value at display time into a string like `"12.5%"` — the row's share
of that column's sum **across the rows the query returned**, rounded to two decimals. It is not a
SQL-side window function.

## Dimensions — `Luminix\Bi\Dimensions`

| Class | Selects and groups by | Notes |
|---|---|---|
| `StringDimension` | `column as key` | groups by the alias |
| `DayDimension` | `DATE_FORMAT(column, '%Y-%m-%d')` | `MonthDimension` is `'%Y-%m'`, `YearDimension` `'%Y'` |
| `RawDimension` | `->raw('CASE WHEN ...') as key` | groups by the alias |
| `BelongsToDimension` | the relation's foreign key | eager-loads the relation for the label |

```php
StringDimension::create('status', 'Status'),
MonthDimension::create('created_at', 'Month'),
BelongsToDimension::create('customer', 'Customer')->otherColumn('name'),
```

The three date dimensions extend `DateDimension`, which emits `DATE_FORMAT` — MySQL and MariaDB
only — and whose carbon settings drive `LineChart` gap filling (-> `widgets.md`). Their `display()`
returns the grouped string as the database produced it, untouched by the model's casts.

`BelongsToDimension` resolves the foreign key from the relation method, whose name defaults to the
dimension key (`->relation()` when they differ). `display()` returns `->otherColumn` of the loaded
related model and then unsets the relation so it does not also appear in the row; `otherColumn` has
no default, so a dimension without it yields nothing useful.
