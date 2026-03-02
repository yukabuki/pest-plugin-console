<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole;

use Pest\Contracts\Plugins\AddsOutput;
use Pest\Contracts\Plugins\Bootable;
use Pest\Contracts\Plugins\HandlesArguments;
use Symfony\Component\Console\Output\ConsoleOutput;
use Yukabuki\PestPluginConsole\Output\ConsoleRenderer;
use Yukabuki\PestPluginConsole\Output\HtmlReportGenerator;
use Yukabuki\PestPluginConsole\Output\NullStreamFilter;
use Yukabuki\PestPluginConsole\Output\ProgressState;
use Yukabuki\PestPluginConsole\Output\StreamingTestRenderer;
use Yukabuki\PestPluginConsole\Results\Subscribers\TestExecutionStartedSubscriber;

use function Termwind\renderUsing;
use Yukabuki\PestPluginConsole\Results\Subscribers\TestFailedSubscriber;
use Yukabuki\PestPluginConsole\Results\Subscribers\TestFinishedSubscriber;
use Yukabuki\PestPluginConsole\Results\Subscribers\TestPassedSubscriber;
use Yukabuki\PestPluginConsole\Results\Subscribers\TestSkippedSubscriber;
use Yukabuki\PestPluginConsole\Results\Subscribers\TestStartedSubscriber;
use Yukabuki\PestPluginConsole\Results\TestResultCollector;

/**
 * @internal
 */
final class Plugin implements Bootable, HandlesArguments, AddsOutput
{
    private const string FLAG             = '--no-console';
    private const string FLAG_SLOW        = '--slow';
    private const string FLAG_LOCALE      = '--locale=';
    private const string FLAG_HTML_REPORT = '--html-report';

    /** @var resource|null */
    private static mixed $stdoutFilter = null;

    /**
     * Registers PHPUnit event subscribers and suppresses Collision/Pest output
     * by attaching a null write-filter on STDOUT.
     * The filter is removed in addOutput() just before our own render.
     */
    public function boot(): void
    {
        TestResultCollector::reset();

        \PHPUnit\Event\Facade::instance()->registerSubscribers(
            new TestExecutionStartedSubscriber(),
            new TestStartedSubscriber(),
            new TestPassedSubscriber(),
            new TestFailedSubscriber(),
            new TestSkippedSubscriber(),
            new TestFinishedSubscriber(),
        );

        NullStreamFilter::register();
        self::$stdoutFilter = stream_filter_append(STDOUT, NullStreamFilter::NAME, STREAM_FILTER_WRITE);
    }

    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    public function handleArguments(array $arguments): array
    {
        if (in_array(self::FLAG, $arguments, true)) {
            // Restore original output — user wants Pest's default display.
            self::removeFilter();
            PluginState::disable();

            return array_values(
                array_filter($arguments, static fn (string $arg): bool => $arg !== self::FLAG)
            );
        }

        if (in_array(self::FLAG_SLOW, $arguments, true)) {
            PluginState::enableSlow();
            $arguments = array_values(
                array_filter($arguments, static fn (string $arg): bool => $arg !== self::FLAG_SLOW)
            );
        }

        foreach ($arguments as $arg) {
            if (str_starts_with($arg, self::FLAG_LOCALE)) {
                $locale = substr($arg, strlen(self::FLAG_LOCALE));

                if ($locale === '') {
                    self::abort('--locale requires a value (e.g. --locale=fr).');
                }

                if (! preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', $locale)) {
                    self::abort("Invalid locale value \"{$locale}\". Expected format: \"fr\" or \"fr_FR\".");
                }

                PluginState::setLocale($locale);
            }
        }

        $arguments = array_values(
            array_filter($arguments, static fn (string $arg): bool => ! str_starts_with($arg, self::FLAG_LOCALE))
        );

        foreach ($arguments as $arg) {
            if ($arg === self::FLAG_HTML_REPORT || str_starts_with($arg, self::FLAG_HTML_REPORT.'=')) {
                $path = $arg === self::FLAG_HTML_REPORT
                    ? 'tests/_output/pest/report.html'
                    : substr($arg, strlen(self::FLAG_HTML_REPORT) + 1);

                if ($path === '') {
                    self::abort('--html-report requires a non-empty path when a value is provided.');
                }

                PluginState::enableHtmlReport($path);
            }
        }

        $arguments = array_values(array_filter($arguments, static fn (string $arg): bool =>
            $arg !== self::FLAG_HTML_REPORT && ! str_starts_with($arg, self::FLAG_HTML_REPORT.'=')
        ));

        // Suppress Pest's own progress/summary output (Collision is handled by the filter).
        if (! in_array('--no-output', $arguments, true)) {
            $arguments[] = '--no-output';
        }

        return $arguments;
    }

    /**
     * Removes the STDOUT filter then renders our custom output.
     */
    public function addOutput(int $exitCode): int
    {
        StreamingTestRenderer::flush();
        ProgressState::clear();
        self::removeFilter();

        if (PluginState::isHtmlReportEnabled()) {
            (new HtmlReportGenerator())->generate(
                path:           PluginState::getHtmlReportPath(),
                results:        TestResultCollector::getResults(),
                duration:       TestResultCollector::getTotalDuration(),
                assertionCount: TestResultCollector::getAssertionCount(),
            );
        }

        if (! PluginState::isEnabled()) {
            return $exitCode;
        }

        // Force ANSI colors — stream filter manipulation can break TTY detection.
        renderUsing(new ConsoleOutput(decorated: true));

        (new ConsoleRenderer())->render(
            results: TestResultCollector::getResults(),
            duration: TestResultCollector::getTotalDuration(),
            assertionCount: TestResultCollector::getAssertionCount(),
        );

        return $exitCode;
    }

    private static function removeFilter(): void
    {
        if (self::$stdoutFilter !== null) {
            stream_filter_remove(self::$stdoutFilter);
            self::$stdoutFilter = null;
        }
    }

    private static function abort(string $message): never
    {
        self::removeFilter();
        fwrite(STDERR, sprintf("\n \033[1;31m[pest-plugin-console]\033[0m %s\n\n", $message));
        exit(1);
    }
}
