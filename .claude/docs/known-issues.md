# Известные ловушки

## Бридж

- **`$exception::class` у анонимного класса** содержит NUL-байт и абсолютный путь к файлу
  (`DomainException@anonymous\0/path/File.php:67$0`) — в end context спана, а значит в экспортёры, пишется
  `get_debug_type()`.
- **Сбой `ActionNameBuilderInterface` не должен ронять Action из-за профилирования** — `ProfilingMiddleware::spanName()`
  падает обратно на класс Action. `LoggingMiddleware` logic-bundle вызывает resolver без защиты, так что при включённом
  логировании Action всё равно упадёт; бридж лишь не добавляет ещё одну точку отказа. Сбой здесь сознательно
  не логируется: логгера у middleware нет, а сообщить о сломанном builder — дело логирования logic-bundle.
- **`ProfilingFactory::createSpan()`/`endSpan()` сами глушат сбои** ассемблеров, процессоров и end-хендлеров
  (логируют), поэтому middleware их не оборачивает. Декоратор фабрики, который бросает, это нарушит.
- **`KernelBridgeTest::testTheMiddlewareRunsOutsideLogging` читает приватное `ActionPipeline::$middlewares`**
  через reflection — публичного API порядка у logic-bundle нет. Если тест упадёт после minor-обновления logic-bundle,
  сначала проверить, не переименовали ли свойство.
- **Имя Action резолвится до трёх раз за вызов** (логирование, профилирование и metrics-bridge-logic, если он
  установлен). Дёшево; мемоизировать по `actionClass` имеет смысл, только если профилирование покажет горячую точку.

## Семейство (Symfony, CI)

- **`bin/init` шаблона даёт namespace с суффиксом `Bundle`** и имя пакета `…-bundle` (историческое: при создании
  репозитория); у бриджей семейства без суффикса — поправлено вручную (composer, deptrac, README, SECURITY, CI slug).
- **`kernel.build_dir` в тестовом контейнере.** `PhpFileLoader` Symfony 7.4 читает этот параметр,
  8.x — нет; без него интеграционные тесты падают только в ячейке 7.4.
- **Теги для своих интерфейсов** регистрировать в `loadExtension()` через
  `registerForAutoconfiguration()`, а не `#[AutoconfigureTag]` на интерфейсе: атрибут на интерфейсе
  из vendor-пакета приложение не подхватывает.
- **`gh run watch` может вернуть 0 на упавшем прогоне.** Итог проверять через
  `gh run view <id> --json conclusion,jobs`.
- **`actions/checkout` по sha делает shallow-клон**; push из такого клона отклоняется как
  "fetch first" — нужен `fetch-depth: 0`.
- **Пороги Infection** (`infection-min-msi`, `infection-min-covered-msi` в `checks.yml`) = замер минус запас,
  с датой замера (UTC) в комментарии; пересчитывать при заметном изменении тестов.
- **`exec()` портит вывод с путями**: режет по строкам и срезает пробелы в конце строки, поэтому
  `git ls-files -z` через него теряет символы в именах. Читать через `proc_open` целиком. В тестах не
  читать stdout и stderr из двух pipe по очереди (deadlock при заполненном буфере) — stderr писать в файл.
- **Код должен разбираться на PHP 8.2** (с 2026-10-14 UTC, профиль `php82` из bundle-standard v2.0.0+): без
  типизированных констант (`const string X`), без анонимных `new readonly class {}` (оба — 8.3;
  именованный `readonly class` — 8.2, можно) и без `new X()->m()` / `new X()(...)` (8.4) — только
  `(new X())->m()`. PHPStan (`phpVersion.min: 80200`) ловит типизированные
  константы; остальное ловят только `php -l` в job «minimal» (PHP 8.2) и ячейки PHPUnit на 8.2. Шаблоны Rector
  (`withPhpVersion(PHP_82)`) и CS-Fixer (`new_expression_parentheses`) синтаксис обратно не поднимают.
  `#[Override]` (8.3) допустим: на 8.2 атрибут не инстанцируется. `array_any/all/find` (8.4) — только с
  `symfony/polyfill-php84` в `require`. Symfony 8 ставится только на PHP 8.4+.
  `composer-ci.lock` собран на PHP 8.4 (Symfony 8): на 8.2/8.3 локально — `COMPOSER=composer-ci.json composer update`
  (или `--prefer-lowest`, как ячейка CI), не `install`.
- **PhpStorm (`.idea/php.xml` на уровне монорепо) стоит на языке 8.5**: его инспекции предлагают вернуть типы
  констант и убрать скобки у `new` — для пакетов на 8.2 это шум, не применять.
