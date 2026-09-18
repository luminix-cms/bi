# Configuration — `config/luminix/bi.php`

Published by `bi:install`, or on its own with `php artisan vendor:publish --tag=bi-config`. The
package merges its own defaults underneath, so a published file may omit any key.

| Key | Env | Default | Purpose |
|---|---|---|---|
| `path` | `LUMINIX_BI_PATH` | `bi` | URI prefix — the API routes append `-apis` to it |
| `middleware` | — | `['web', 'auth', 'can:read-bi-reports']` | wraps every BI route |
| `connection` | `BI_DB_CONNECTION` | `null` | connection for BI queries; `null` means the model's own |
| `debug` | `LUMINIX_BI_DEBUG` | `false` | attaches the query log to widget responses |

- the default middleware assumes session auth plus the `read-bi-reports` gate, which lives in the
  provider `bi:install` publishes (-> `getting-started.md`). For a token-authenticated client, swap
  `web` for `api` — mirroring `luminix.backend.security.middleware`
- `connection` is applied by the query service the widgets share, so it also covers `RelationFilter`
  option lists, but not `StringFilter`'s (-> `filters.md`)

## Boot payload

When the current user has at least one viewable dashboard, the package publishes `luminix.bi.path`
into `luminix/frontend`'s boot config, so the client derives the API prefix instead of keeping a
second copy of it. A user with no viewable dashboard receives no key at all — that absence is the
signal that BI is not available to them.
