# Pest Plugin Console

A [Pest 4](https://pestphp.com) plugin that enhances test output using [Symfony Console](https://symfony.com/doc/current/components/console.html), [Termwind](https://github.com/nunomaduro/termwind), and [Symfony Translation](https://symfony.com/doc/current/translation.html).

## Features

- **Enhanced output** — rich, coloured terminal output powered by Termwind
- **Multi-language support** — English and French built-in, with a simple API to add your own locale
- **Zero-config opt-out** — pass `--no-console` to any Pest command to fall back to the original Pest output

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

Pass `--no-console` to disable the plugin for that run:

```bash
./vendor/bin/pest --no-console
```

### Change the language

The plugin defaults to English. To switch to French (or any other supported locale), configure the `TranslationManager` in your `Pest.php` bootstrap file:

```php
// tests/Pest.php
use Yukabuki\PestPluginConsole\Translations\TranslationManager;

$manager = new TranslationManager(locale: 'fr');
```

### Add your own translation

Create a PHP file that returns an associative array of translation keys and values:

```php
// lang/de/messages.php
return [
    'tests.passed'  => 'Tests bestanden!',
    'tests.failed'  => 'Tests fehlgeschlagen.',
    'tests.summary' => ':passed bestanden, :failed fehlgeschlagen, :skipped übersprungen (:total gesamt)',
    'run.start'     => 'Testsuite wird ausgeführt...',
    'run.duration'  => 'Abgeschlossen in :time Sekunden.',
];
```

Then register it in your `Pest.php`:

```php
// tests/Pest.php
use Yukabuki\PestPluginConsole\Translations\TranslationManager;

$manager = new TranslationManager(locale: 'de');
$manager->addResourcePath(__DIR__.'/../lang/de/messages.php', 'de');
```

### Available translation keys

| Key | Description | Default (EN) |
|---|---|---|
| `tests.passed` | All tests passed | `Tests passed!` |
| `tests.failed` | One or more tests failed | `Tests failed.` |
| `tests.summary` | Summary line with counts | `:passed passed, :failed failed, :skipped skipped (:total total)` |
| `run.start` | Displayed when the suite starts | `Running test suite...` |
| `run.duration` | Displayed after the run completes | `Completed in :time seconds.` |

Parameters (`:passed`, `:failed`, etc.) are replaced automatically at runtime.

## Contributing

Contributions are welcome. Please open an issue or submit a pull request on [GitHub](https://github.com/yukabuki/pest-plugin-console).

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
