# Writing your own block

Each of the five block types has one method you must implement; everything else is inherited.

**Neighbours:** what the built-in classes already do -> `widgets.md`, `metrics-and-dimensions.md`,
`filters.md` · where your `apply()` runs -> `query-pipeline.md`.

## Widget

Extend `BaseWidget` and declare `protected $component` — the string the frontend dispatches on.
`HasAttributes` is already part of the base class, so metrics and dimensions work with no extra
wiring.

```php
class Funnel extends BaseWidget
{
    protected $component   = 'funnel';
    protected $orientation = 'vertical';

    public function data(Dashboard $dashboard, BiRequest $request)
    {
        return parent::data($dashboard, $request)->reverse()->values();
    }

    protected function extra(): array
    {
        return ['orientation' => $this->orientation];
    }
}
```

`getBaseBuilder()`, `applyAttributes()` and `applyFilters()` are protected and can be composed in a
different order if `parent::data()` is not what you want. Overriding `extra()` replaces the default
payload entirely, `uniqid` included.

## Metric

Extend `BaseMetric` and implement `apply(Builder $builder, Widget $widget): Builder`, adding a
select aliased to `$this->key` — the alias is what `display()` and request sorting look for.
Override `display(Model $value, array $models)` to format the value (the second argument is the
whole raw result set) and `getEmptyValue()` to change what line-chart gap filling inserts.

## Dimension

Extend `BaseDimension` and implement the same `apply()`, adding both the select and the `groupBy`.
Override `display()` for labels and `applySort()` when the sortable column is not the select alias —
as `BelongsToDimension` does to sort by the foreign key.

## Date granularity

Extend `DateDimension` and configure it in the constructor:

```php
public function __construct($key, $name)
{
    parent::__construct($key, $name);
    $this->sqlFormat('%Y-%u');               // the DATE_FORMAT mask used to select and group
    $this->carbonFormat('Y-W', 'week');      // how to parse that string back, and the step
    $this->carbonFunctions('startOfWeek', 'endOfWeek');
}
```

The interval and the two Carbon method names are exactly what `LineChart` uses to walk the period
and to snap a filter's range, so a new granularity gets gap filling for free.

## Filter

Extend `BaseFilter`, declare `public $component` (public: filters are serialized from their public
properties) and implement
`apply(Builder $builder, array $filterData, BiRequest $request): Builder`, where `$filterData` is
whatever the request sent under this filter's key. Override `extra(Dashboard $dashboard, BiRequest
$request): array` to serve an option list to the frontend.

For anything relation-shaped, extend `BaseRelationFilter` instead: it adds `->relation()` and
`->otherColumn()`, and resolves a dotted relation path from either a `Dashboard` or a `Builder`.
