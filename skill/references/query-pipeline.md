# The query pipeline

Every widget builds its query in this order. Knowing it answers most "why is my `where` being
overridden / ignored" questions.

1. **Base builder** — the dashboard's `$model`, on the connection in `luminix.bi.connection` when
   one is set, otherwise the model's own. Authorization is attached here, below.
2. **Dashboard `scope()`**, if the dashboard defines the method.
3. **Widget `->scope()`**, called with `$this` rebound to the widget.
4. **Attributes** — `apply()` on each metric in declaration order, then on each dimension, adding
   the `SELECT` fragments and the `GROUP BY`.
5. **Filters** — every dashboard filter whose key is present in the request payload, in the order
   `filters()` returns them (-> `filters.md`).
6. **Display** — the rows are fetched and each one is rebuilt as a fresh object holding only the
   attribute keys, passing each value through the attribute's `display()`. The full raw result set
   is handed to `display()` as well, which is how `asPercentage()` reaches the column total.

`Table` inserts request sorting between 5 and 6, and `LineChart` post-processes the finished rows
(-> `widgets.md`).

There is no pagination anywhere: a widget returns every row its grouping produces.

## Authorization

For a model using `luminix/backend`'s `LuminixModel` trait, the base builder gets `allowed()`
applied with the permission configured for the `index` endpoint (`read` by default), so the same
row-level `scopeAllowed()` that filters the REST API filters the dashboards — with no per-dashboard
setup. Setting `luminix.backend.security.gates_enabled` to `false` turns this off, here as well.

A plain Eloquent model reaches no gate at all. For those, the only protection is the route
middleware (`configuration.md`), the dashboard's `viewable()` and its `scope()`.

No Gate is evaluated per widget — `allowed()` is a row filter, not an ability check, so a user who
may reach no rows gets empty widgets rather than an error. And the trait's `scopeAllowed()` is an
empty default: a Luminix model that does not implement it leaves BI queries unfiltered.
