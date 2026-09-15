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
subquery and the option list, and is called with `$this` rebound to the filter.

## Option lists

Only two filters answer the filter endpoint with anything:

- `StringFilter` returns the distinct values of its column. It queries the dashboard model directly,
  so the BI connection and the backend row-level scope do **not** apply to this list, even though
  they apply to the widget data (-> `query-pipeline.md`)
- `RelationFilter` returns `{options, otherColumn, primaryKey}`, selecting the primary key and
  `otherColumn` (falling back to `name`) from the related model through the same query service the
  widgets use — so the connection and, for a Luminix model, `allowed()` do apply here

Every other filter returns an empty `extra`.
