# Pest Plugin Console

A [Pest 4](https://pestphp.com) plugin that enhances test output using [Symfony Console](https://symfony.com/doc/current/components/console.html), [Termwind](https://github.com/nunomaduro/termwind), and [Symfony Translation](https://symfony.com/doc/current/translation.html).

## Features

- **Real-time output** — test results stream to the terminal as they run, with a progress bar
- **Rich colours** — PASS / FAIL / WARN badges and coloured summary table
- **Multi-language support** — English and French built-in, simple API to add your own locale
- **Zero-config opt-out** — pass `--no-console` to fall back to the original Pest output

## Requirements

- PHP `^8.3`
- Pest `^4.0`

## Installation

```bash
composer require yukabuki/pest-plugin-console
```

The plugin is auto-discovered by Pest — no extra configuration needed.

## Usage

### Default (enhanced output)

```bash
./vendor/bin/pest
```

### Opt out — original Pest output

```bash
./vendor/bin/pest --no-console
```

### Change the display language

Pass `--locale=XX` to switch language for that run:

```bash
./vendor/bin/pest --locale=fr
```

Built-in locales: `en` (default), `fr`.

### Add your own translation

**1 — Create a translation file** that returns an associative array:

```php
// lang/de/messages.php
return [
    'section.tests'    => 'TESTS',
    'section.fail'     => 'FEHLER',
    'section.report'   => 'BERICHT',

    'badge.pass'       => 'PASS',
    'badge.fail'       => 'FAIL',
    'badge.warn'       => 'WARN',

    'table.passed'     => 'Bestanden',
    'table.failed'     => 'Fehlgeschlagen',
    'table.skipped'    => 'Übersprungen',
    'table.total'      => 'Gesamt',
    'table.assertions' => 'Assertions',
    'table.duration'   => 'Dauer',
    'table.avg'        => 'Ø',
];
```

**2 — Register it** in your `tests/Pest.php` bootstrap file:

```php
// tests/Pest.php
use Yukabuki\PestPluginConsole\Translations\TranslationManager;

TranslationManager::instance()->addResourcePath(__DIR__.'/../lang/de/messages.php', 'de');
```

**3 — Run with the locale flag:**

```bash
./vendor/bin/pest --locale=de
```

### Available translation keys

| Key | Description | Default (EN) |
|---|---|---|
| `section.tests` | Streaming section title | `TESTS` |
| `section.fail` | Failure details section title | `FAIL` |
| `section.report` | Summary table section title | `REPORT` |
| `badge.pass` | Badge when all tests in a class pass | `PASS` |
| `badge.fail` | Badge when at least one test fails | `FAIL` |
| `badge.warn` | Badge when tests are skipped | `WARN` |
| `table.passed` | Summary table column | `Passed` |
| `table.failed` | Summary table column | `Failed` |
| `table.skipped` | Summary table column | `Skipped` |
| `table.total` | Summary table column | `Total` |
| `table.assertions` | Summary table column | `Assertions` |
| `table.duration` | Summary table column | `Duration` |
| `table.avg` | Summary table column | `Avg` |

### Debug — slow mode

Add a 500 ms delay between each test to inspect real-time output:

```bash
./vendor/bin/pest --slow
```

## Contributing

Contributions are welcome. Please open an issue or submit a pull request on [GitHub](https://github.com/yukabuki/pest-plugin-console).

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
