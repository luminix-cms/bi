# Developing `luminix/bi`

`src/` is the whole product; everything else here is tests, documentation or packaging.

## Two audiences, two trees

| Tree | Written for | Language | Ships |
|---|---|---|---|
| `skill/SKILL.md` + `skill/references/` | an agent **consuming** the package in an app | English | yes |
| `AGENTS.md` (this file), `CLAUDE.md` | an agent **developing** the package | English | no |
| `readme.md` | a human landing on Packagist | Portuguese | yes |
| `docs/` | a human reading at tutorial length | Portuguese | no |

`.gitattributes` decides what ships. `git archive HEAD | tar -t` must list `skill/`, and never this
file, `CLAUDE.md`, `docs/`, `tests/` or the test config.

An app that ran `vendor:publish --tag=luminix-skill` holds a copy of `skill/`, so a fix here
reaches it on that app's next publish with `--force`.

## Writing `skill/`

- update it when a change is observable from a consuming app: a route, a status code, a config key,
  a block class, a request payload, a published boot key. Internal refactors leave it alone
- an API described there that `src/` does not have is a bug in `skill/`
- it describes the behaviour of this commit. What an older release did belongs to the release notes
- every sentence serves the reader's current task and says something the agent could not get from a
  glance at the repository
- describe the package, not the documentation system: no prose about where the guide ships from,
  how skills are found, or what else exists in the ecosystem. Name the neighbouring package when
  the answer lives outside this one

## Working here

There is no host app and no workbench: `orchestra/testbench` builds a throwaway Laravel, and each
test that touches the database creates its own SQLite schema in `setUp()`. There is no local `php`
either — run everything through the image:

```bash
docker run --rm -u 1002:1002 -v "$PWD":/app -w /app php-composer:8.3-imagick composer test
docker run --rm -u 1002:1002 -v "$PWD":/app -w /app php-composer:8.3-imagick \
    vendor/bin/testbench package:test --filter=WidgetFeaturesTest
```

`luminix/frontend`'s `BootService` keeps its reducers in a **static** property, so a test that lets
`BiServiceProvider::boot()` register one must call `BootService::flushReducers()` in `tearDown()` or
it leaks into every later test in the process.

`DashboardResolver` is built on every boot payload now, not only on the BI routes — keep its
constructor cheap and incapable of throwing, or every page of an app without `app/Bi/Dashboards`
breaks.

## Git

- `v1.x` is the release branch; work on `feat/`/`fix/` branches and merge into it. `origin/HEAD`
  still points at `v0.x`, so a fresh clone lands on the previous line
- every push to `v1.x` runs the 10-job matrix (PHP 8.2–8.5 × Laravel 11–13) and, if it is green,
  stamps a tag and a GitHub Release with generated notes. `tag_prefix` is empty, unlike the `v1.1.x`
  tags already in the repo
- semver comes from the commit subject: `(MAJOR)` -> major, `(MINOR)` -> minor, absence -> patch
- commit messages and branch names in português
