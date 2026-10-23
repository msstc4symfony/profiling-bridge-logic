# Changelog

## 2.0.1 - 2026-10-23

- Changed: allows logic-bundle 2.0 (`^1.3|^2.0`); 2.0 only marks logic-bundle internals `@internal`, none of
  which the bridge uses.

## 2.0.0 - 2026-10-20

- Changed: requires the final 2.0 releases (`^2.0` instead of `^2.0@rc`).

## 2.0.0-rc.1 - 2026-10-19

- Changed: requires profiling-bundle ^2.0 (`^2.0@rc` until profiling 2.0.0 is final; `Application\ProfilingFactoryInterface` replaces
  `Framework\ProfilingFactoryInterface` in the `ProfilingMiddleware` constructor) and logic-bundle ^1.3.
- Changed: `ProfilingMiddleware` is `@internal`; the public API is the bundle class (README "Public API").
- Changed: PHPStan enforces `#[\Override]` (`checkMissingOverrideMethodAttribute`); adopts bundle-standard v3
  (canonical deptrac layers, anchored `BundleRoot`).

## 1.1.0 - 2026-10-14

- Changed: requires PHP 8.2 instead of 8.4 (Symfony 8 still needs PHP 8.4).
- Changed: class constants no longer declare a native type (PHP 8.2 has none); values are unchanged.
- Changed: requires logic-bundle ^1.2 and profiling-bundle ^1.1, their PHP 8.2 releases.

## 1.0.0 - 2026-10-12

- First release: an `action <name>` profiling span, ended with the outcome, around every logic-bundle Action.
