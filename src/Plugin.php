<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole;

use Pest\Contracts\Plugins\AddsOutput;
use Pest\Contracts\Plugins\Bootable;
use Pest\Contracts\Plugins\HandlesArguments;
use Yukabuki\PestPluginConsole\Output\ConsoleRenderer;
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
    /**
     * CLI flag that falls back to Pest's original Collision output.
     * Must also be handled in bin/pest-console before the autoloader loads.
     */
    private const string FLAG = '--no-console';

    /**
     * Registers PHPUnit event subscribers to collect test results.
     * Called before the test suite runs.
     */
    public function boot(): void
    {
        TestResultCollector::reset();

        \PHPUnit\Event\Facade::instance()->registerSubscribers(
            new TestStartedSubscriber(),
            new TestPassedSubscriber(),
            new TestFailedSubscriber(),
            new TestSkippedSubscriber(),
            new TestFinishedSubscriber(),
        );
    }

    /**
     * Detects --no-console and disables the plugin for this run.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    public function handleArguments(array $arguments): array
    {
        if (! in_array(self::FLAG, $arguments, true)) {
            return $arguments;
        }

        PluginState::disable();

        return array_values(
            array_filter($arguments, static fn (string $arg): bool => $arg !== self::FLAG)
        );
    }

    /**
     * Renders the custom Termwind output after all tests complete.
     * Does nothing when disabled via --no-console (Collision handles output).
     */
    public function addOutput(int $exitCode): int
    {
        if (! PluginState::isEnabled()) {
            return $exitCode;
        }

        (new ConsoleRenderer())->render(
            results: TestResultCollector::getResults(),
            duration: TestResultCollector::getTotalDuration(),
            assertionCount: TestResultCollector::getAssertionCount(),
        );

        return $exitCode;
    }
}
