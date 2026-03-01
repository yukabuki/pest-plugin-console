<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole;

use Pest\Contracts\Plugins\AddsOutput;
use Pest\Contracts\Plugins\Bootable;
use Pest\Contracts\Plugins\HandlesArguments;
use Symfony\Component\Console\Output\ConsoleOutput;
use Yukabuki\PestPluginConsole\Output\ConsoleRenderer;
use Yukabuki\PestPluginConsole\Output\NullStreamFilter;
use Yukabuki\PestPluginConsole\Output\ProgressState;
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
    private const string FLAG      = '--no-console';
    private const string FLAG_SLOW = '--slow';

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
        ProgressState::clear();
        self::removeFilter();

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
}
