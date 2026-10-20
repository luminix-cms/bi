# Filters

Filters are declared once on the dashboard and applied to **every** widget of that dashboard, but
only for the keys present in the request's `filters` payload. The match is `$filter->key` against
the payload key; the column touched is `column`, which defaults to the key.

**Neighbours:** where filters sit in the query -> `query-pipeline.md` · the endpoint serving option
lists -> `api.md` · the date filter that also drives line-chart gap filling -> `widgets.md`.

```php
public function filters()
{
    return [
        DateIntervalFilter::create('created_at', 'Period')
            ->defaultDates(now()->subDays(30), now()),
        RelationFilter::create('customer', 'Customer')->otherColumn('name'),
    ];
}
```

| Class (`Luminix\Bi\Filters\*`) | `component` | Payload under `filters[key]` | Applies |
|---|---|---|---|
| `DateFilter` | `date` | `['2026-07-13']` | `whereBetween` over that whole day |
| `DateIntervalFilter` | `date-interval` | `['start' => '2026-07-01', 'end' => '2026-07-31']` | `whereBetween`, start of the first day to end of the last |
| `StringFilter` | `string` | `['active', 'draft']` | `whereIn` |
| `NumberFilter` | `number` | `['operator' => '>', 'values' => [10]]` | `where(col, op, values[0])`, or `whereBetween(col, values)` when the operator is `between` |
| `RelationFilter` | `belongs-to` | `[1, 2, 3]` — keys of the **related** model | `whereHas` on the relation + `whereIn` on its primary key |

`DateFilter` parses with an exact `Y-m-d` format and throws on anything else; `DateIntervalFilter`
goes through `Carbon::parse` and accepts what Carbon accepts. `NumberFilter` with an empty
`operator` is a no-op rather than an error.

`->defaultValue($value)` (and its typed shortcuts `->defaultDate()` / `->defaultDates()`) is
published to the frontend and **never applied server-side**: a request that omits the key is
unfiltered, whatever the default says.

Filters have no `jsonSerialize()`, so what the frontend receives is their public properties —
`key`, `name`, `column`, `defaultValue`, `component`, plus `relation` and `otherColumn` on
`RelationFilter`.

## `RelationFilter`

The relation name defaults to the filter key; `->relation()` overrides it and accepts a dotted path
(`'company.sector'`), which is walked to find the related model and then handed to `whereHas` as-is.
`->otherColumn('name')` names the label column, `->scope(Closure)` narrows both the matching
subquery and the option list, and is called with `$this` rebound to the filter. Its wheres are
grouped, so an `orWhere` in it can't widen the selection past the ids the user picked. `->allOptions()`
lists every related record instead of only the ones the dashboard's rows reach (-> Option lists).

## Option lists

Only two filters answer the filter endpoint with anything:

- `StringFilter` returns the distinct values of its column, sorted, built on the same base query as
  the widget rows: the BI connection, `allowed()` for a Luminix model, and the dashboard's `scope()`
  (-> `query-pipeline.md`). Widget `->scope()`s and the other filters are not applied, so the list
  covers the dashboard's rows, not one widget's. When the dashboard's `scope()` joins a table that
  shares the column name, qualify it — `->column('orders.status')` — or both the list and `apply()`
  fail as ambiguous
- `RelationFilter` returns `{options, otherColumn, primaryKey}`, selecting the primary key and
  `otherColumn` (falling back to `name`) from the related model, restricted to the records the
  dashboard's rows reach through the relation: the base query is the same one the widgets use (BI
  connection, `allowed()` for a Luminix model, the dashboard's `scope()`), and the relation path is
  followed from it. A related record no dashboard row references is not listed, so a category with
  no orders is absent. On top of that the related model's own `allowed()` and the filter's
  `->scope()` apply; that `->scope()` is grouped like the dashboard's, so an `orWhere` in it can't
  widen the list past the restriction. `->allOptions()` drops the restriction to the dashboard's rows
  and keeps those two. Widget `->scope()`s and the other filters are not applied, so the list covers
  the dashboard's rows, not one widget's
  - the path may be dotted and cross `BelongsTo`, `HasOne`, `HasMany`, `BelongsToMany`,
    `MorphOne`/`MorphMany`, `MorphToMany` (`morphedByMany` too), `HasOneThrough` and
    `HasManyThrough` relations. Any other relation class, such as one from a package, makes
    `extra()` throw a `LogicException` naming `->allOptions()`, which lists that relation's records
  - a `MorphTo` is not supported: its related model is the one declaring it, so `extra()` throws a
    `LogicException` even with `->allOptions()`
  - models the path crosses contribute only their global scopes (`SoftDeletes`), and the
    intermediate model of a `*Through` relation only its `SoftDeletes`: their `allowed()` is not
    applied, as in `apply()`'s `whereHas`, and constraints declared inside the relation method
    (`->where()`, `wherePivot()`) are ignored, so such a relation can offer a record that matches no
    row
  - the restriction is an `IN (subquery)` over the dashboard's rows; on a large table whose foreign
    key has no index, `->allOptions()` avoids the cost

Every other filter returns an empty `extra`.
