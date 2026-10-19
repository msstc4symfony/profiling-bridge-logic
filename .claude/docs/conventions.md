# Соглашения

- Код бриджа — в `src/Infrastructure/Profiling/`; Application-слоя нет. Раскладка и правила семейства — спек этапа B
  (`docs/superpowers/specs/2026-10-17-stage-b-conventions-design.md` в корне msstc4symfony).
- Constraint на rc зависимости — `^2.0@rc` только в rc-релизе моста; в финальном — `^2.0`.
- Тесты: `tests/Unit/<Класс>Test.php` с `#[CoversClass]`; unit-тест middleware использует настоящий `ProfilingFactory`
  (`SpanAssembler([])` пропускает все спаны) и шпиона `tests/Fixtures/RecordingEndProcessor` (тип записи —
  `@phpstan-type EndedSpanShape`, читать через `ended()`, ожидание для спана верхнего уровня — `topLevelSpan()`).
- `tests/Integration/KernelBridgeTest` поднимает настоящий `TestKernel` (Framework, Monolog, Profiling, Logic, бридж);
  `RecordingEndProcessor` регистрируется autoconfigure-сервисом и подхватывается тегом `EndSpanProcessorInterface`.
- Длинные строки в тестах допустимы: конфиг CS семейства их не ограничивает (как в metrics-bridge-logic).
- `InnerAction` — нейтральный вложенный Action для теста дочернего спана. Фикстуры `Echo*`, `FailingAction`, `ExceptionHandlerSnapshot` скопированы из metrics-bridge-logic (namespace заменён).
- Infection гоняет только unit-сьют: всё, что должно убивать мутантов, — в `tests/Unit`.
- Файлы инструментов байт-в-байт из `bundle-standard/templates`; менять их можно только в стандарте.
- Коммиты — одна строка на английском, ≤ 20 слов; теги `vX.Y.Z`.
