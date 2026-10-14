# Changelog

## 1.1.0 - 2026-10-14

- Changed: requires PHP 8.2 instead of 8.4 (Symfony 8 still needs PHP 8.4).
- Changed: class constants no longer declare a native type (PHP 8.2 has none); values are unchanged.
- Changed: requires logic-bundle ^1.2 and profiling-bundle ^1.1, their PHP 8.2 releases.

## 1.0.0 - 2026-10-12

- First release: an `action <name>` profiling span, ended with the outcome, around every logic-bundle Action.
