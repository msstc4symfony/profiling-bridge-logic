# Архитектура

- `ProfilingBridgeLogicBundle` — `AbstractBundle` без дерева конфигурации и без `build()`: `loadExtension()`
  импортирует `src/Resources/config/services.php`.
- `Infrastructure/Profiling/ProfilingMiddleware` — `#[AsActionMiddleware(priority: 500)]`, автоконфигурируется в
  пайплайн logic-bundle; самый внешний, поэтому спан покрывает metrics-bridge-logic (400), логирование (300),
  валидацию (200) и lock (100).
  - Сообщение спана: `SPAN_PREFIX` (`'action '`) + имя из `ActionNameResolver`; стабильный префикс нужен для
    `msstc4symfony_profiling.spans.whitelist|blacklist` (матчинг по `str_starts_with`).
  - End context: `outcome` (`ActionOutcome::of()`) и при сбое `exception` = `get_debug_type()`.
  - Вложенный Action получает дочерний спан: родителя ставит стек `ProfilingFactory`, middleware ничего не передаёт.
- Сервисы приватные, id = FQCN. Без ProfilingBundle контейнер не компилируется (нет `ProfilingFactoryInterface`) —
  отдельного compiler pass нет, ошибка автовайринга достаточно понятна.
- deptrac: код только в Infrastructure; BundleRoot от него не зависит (`services.php` — не класс).
- Имя и исход Action берутся из logic-bundle (`ActionNameResolver`, `ActionOutcome`), чтобы логи, метрики и спаны совпадали.
