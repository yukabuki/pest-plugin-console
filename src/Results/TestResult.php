<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Results;

/**
 * @internal
 */
final class TestResult
{
    public function __construct(
        /** Raw class name used for grouping (e.g. P\Tests\ExampleTest). */
        public readonly string $className,
        /** Human-readable class name for display (e.g. Tests\ExampleTest). */
        public readonly string $displayClass,
        /** Human-readable test name (e.g. "it passes"). */
        public readonly string $testName,
        public readonly string $status, // 'passed' | 'failed' | 'skipped'
        public readonly float $duration,
        public readonly ?FailureDetails $failure = null,
    ) {}
}
