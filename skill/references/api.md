# HTTP API

Everything is served under `{luminix.bi.path}-apis` (`bi-apis` by default) behind the middleware in
`luminix.bi.middleware` (-> `configuration.md`). The routes carry no names: build URLs from the
configured path rather than reaching for `route()`.

| Method | URI | Body |
|---|---|---|
| GET | `/bi-apis/dashboards` | `{status, data}` — every dashboard this user may view |
| GET | `/bi-apis/{dashboard}/widgets` | `{status, data}` — `uriKey`, `name`, `csvEnabled`, `widgets`, `filters` |
| GET | `/bi-apis/{dashboard}/widgets/{widget}` | `{status, data}` — the widget's rows |
| GET | `/bi-apis/{dashboard}/widgets/{widget}/csv` | streamed CSV of the same rows |
| GET | `/bi-apis/{dashboard}/filters/{filter}` | `{status, extra}` — the filter's option payload |

`{dashboard}` is the dashboard's `$uriKey`, `{widget}` the widget's `key`, `{filter}` the filter's
`key`. The `status` field is always the literal `200`; failures are HTTP aborts, not envelopes.

`404` covers every miss: an unknown `uriKey`, a `uriKey` whose dashboard answered `false` to
`viewable()` (indistinguishable from not existing, deliberately), an unknown widget or filter key,
and the CSV route on a dashboard that does not use `HasCsvOutput`.

## Request parameters

- `filters[{key}]=...` — one entry per filter, shaped as that filter class expects
  (-> `filters.md`). Read with `input()`, so query string or JSON body both work
- `sort[col]` + `sort[dir]` — `Table` only (-> `widgets.md`)

Nothing else is read from the request. There are no pagination parameters.

## CSV download

Streams `text/plain` as an attachment named after the widget's name, slugified. The header row is
taken from the keys of the first row, and each row is read with `get_object_vars()` — so an empty
result set errors, and so does a `LineChart` whose gap filling inserted synthetic periods, since
those rows are arrays rather than objects. Tables and pies are the safe cases.

## Debug output

With `luminix.bi.debug` on, the widget-data endpoint — and only that one — enables the query log and
returns it under a `debug` key alongside `status` and `data`.
