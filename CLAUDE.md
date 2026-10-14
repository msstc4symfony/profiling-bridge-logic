# CLAUDE.md

Guidance for Claude Code in this repository. Deep references live under `.claude/docs/`.

## What this is

Symfony bundle (`msstc4symfony/profiling-bridge-logic`, namespace `Msstc4Symfony\ProfilingBridgeLogic`, extension
alias `msstc4symfony_profiling_bridge_logic`, no configuration tree) that connects `msstc4symfony/logic-bundle` to
`msstc4symfony/profiling-bundle`: every Action run gets an `action <name>` span, ended with its outcome.
PHP >= 8.2, Symfony 7.4 / 8.x.

## Common commands

Develop against the CI profile: `COMPOSER=composer-ci.json composer install`.

- `make check` — `php -l`, PHPStan level 10, PHP-CS-Fixer, `composer validate --strict`,
  `composer audit`, Rector dry-run, deptrac. Run as `COMPOSER=composer-ci.json make check`.
- `make test` — unit + integration suites; `make infection`, `make fix`.
- `php ../bundle-standard/bin/verify-standard.php .` — standard compliance (the same check CI runs).

## Architecture in 60 seconds

- `Infrastructure\Profiling\ProfilingMiddleware` — logic-bundle `ActionMiddlewareInterface`, priority 500 (outermost,
  outside metrics-bridge-logic 400 and logging 300). Span message `SPAN_PREFIX . ActionNameResolver::resolve()`
  (a throwing name builder falls back to the Action class); end context `outcome` from `ActionOutcome::of()` plus
  `exception` = `get_debug_type()` on failure; rethrows the same exception.
- `ProfilingFactory` (profiling-bundle) guards `createSpan()`/`endSpan()` itself, so the middleware needs no guard there;
  nested Actions become child spans through the factory's span stack.
- `ProfilingBridgeLogicBundle` only imports `src/Resources/config/services.php`; without ProfilingBundle autowiring fails.

Details: `.claude/docs/architecture.md`. **Read `.claude/docs/known-issues.md` before chasing a
"weird" failure.**

## Pointers

- `.claude/docs/architecture.md` — layers, wiring, service ids.
- `.claude/docs/conventions.md` — layout, tests, commits.
- `.claude/docs/known-issues.md` — bridge and family-wide Symfony/CI traps.
