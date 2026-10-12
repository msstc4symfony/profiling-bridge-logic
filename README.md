# msstc4symfony/profiling-bridge-logic

![Build Status](https://github.com/msstc4symfony/profiling-bridge-logic/actions/workflows/checks.yml/badge.svg?branch=main)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Opens a [profiling-bundle](https://github.com/msstc4symfony/profiling-bundle) span around every
[logic-bundle](https://github.com/msstc4symfony/logic-bundle) Action run, so Actions show up in the span tree
of the request, command or message that called them.

## Compatibility

| Bundle | PHP  | Symfony  | logic-bundle | profiling-bundle |
|--------|------|----------|--------------|------------------|
| 1.x    | 8.4+ | 7.4, 8.x | ^1.1         | ^1.0             |

## Installation

The packages are not on Packagist yet, so register their GitHub repositories first:

```sh
composer config repositories.msstc4symfony-logic vcs https://github.com/msstc4symfony/logic-bundle
composer config repositories.msstc4symfony-profiling vcs https://github.com/msstc4symfony/profiling-bundle
composer config repositories.msstc4symfony-profiling-bridge-logic vcs https://github.com/msstc4symfony/profiling-bridge-logic
composer require msstc4symfony/profiling-bridge-logic
```

Register the bridge in `config/bundles.php` alongside the two bundles it connects (order does not matter):

```php
Msstc4Symfony\ProfilingBundle\ProfilingBundle::class => ['all' => true],
Msstc4Symfony\LogicBundle\LogicBundle::class => ['all' => true],
Msstc4Symfony\ProfilingBridgeLogic\ProfilingBridgeLogicBundle::class => ['all' => true],
```

Nothing to configure. Without ProfilingBundle the container fails to compile: `ProfilingMiddleware` cannot be
autowired, as no `ProfilingFactoryInterface` service exists.

## The span

- Message: `action <name>`, where `<name>` is the name logic-bundle uses in its logs (`ActionNameResolver`):
  the Action class, unless an `ActionNameBuilderInterface` service supports it. A name builder that throws falls
  back to the Action class instead of failing the Action.
- End context: `outcome` — `success`, `expected_failure` or `error`, as logic-bundle's `ActionOutcome`
  classifies the exception — plus `exception` (its `get_debug_type()`, so `DomainException@anonymous` for an
  anonymous class) when the Action threw.
- An Action called from another Action gets a child span of the caller's span.
- The exception is always rethrown unchanged; profiling-bundle logs failures of its own processors instead of throwing them.

The bridge is an Action middleware at priority 500, the outermost one: the span also covers
[metrics-bridge-logic](https://github.com/msstc4symfony/metrics-bridge-logic) (400), logging (300),
validation (200) and lock (100).

Select Action spans by their prefix with profiling-bundle's span lists, for example to skip health checks:

```yaml
msstc4symfony_profiling:
    spans:
        blacklist: ['action App\Application\Health']
```

With [metrics-bridge-profiling](https://github.com/msstc4symfony/metrics-bridge-profiling) these spans also become
observations of `symfony_profiling_span_duration_histogram_seconds`.

## Local development

`composer-ci.json` installs logic-bundle and profiling-bundle from their GitHub repositories.

```sh
make install-ci                        # composer-ci.json profile with every CI tool
COMPOSER=composer-ci.json make check   # lint, PHPStan, CS, composer validate/audit, Rector, deptrac
make test                              # unit + integration
```

## License

MIT — see [LICENSE](LICENSE).
