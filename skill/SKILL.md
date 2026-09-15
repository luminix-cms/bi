---
name: luminix-bi
description: luminix/bi — analytical dashboards declared as PHP classes and served as JSON. Dashboard discovery and the bi:* commands, the four widget types, metrics and dimensions, filters, the query pipeline and the gates it inherits, the HTTP endpoints, writing your own block, config keys. Read this before crawling vendor/luminix/bi/src.
allowed-tools: Read(.claude/skills/luminix-bi/**), Read(vendor/luminix/bi/**)
---

# `luminix/bi`

Laravel package for analytical dashboards. A dashboard is a class in `app/Bi/Dashboards/` that names
a model and composes widgets, metrics, dimensions and filters; the package turns it into SQL and
serves JSON. No server-rendered UI — a frontend consumes the endpoints.

## Where to read

| Read this | When |
|---|---|
| `references/getting-started.md` | installing, `bi:install` and `bi:dashboard`, a first dashboard, why it does not appear |
| `references/widgets.md` | picking a widget, layout and `extra`, table sorting, line-chart gap filling |
| `references/metrics-and-dimensions.md` | choosing an aggregate or a grouping, relation metrics, date granularity, value formatting |
| `references/filters.md` | declaring a filter, the payload each one expects, default values, option lists |
| `references/query-pipeline.md` | the order the query is assembled in, which authorization applies, the read-replica connection |
| `references/api.md` | endpoints, request params, response envelope, CSV download, debug output |
| `references/configuration.md` | `config/luminix/bi.php` and what reaches the boot payload |
| `references/extending.md` | writing a widget, metric, dimension, date granularity or filter of your own |

## Owned elsewhere

- the deny-first Gates and `scopeAllowed()` that BI queries inherit -> `luminix/backend`
- the boot payload that carries the BI path to the browser -> `luminix/frontend`
- rendering these payloads in React -> `@luminix/react-dashboards`
