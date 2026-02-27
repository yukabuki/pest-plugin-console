<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Results;

/**
 * @internal
 */
final class TestResultCollector
{
    /** @var array<int, TestResult> */
    private static array $results = [];

    /** @var array<string, float> test_id => microtime */
    private static array $startTimes = [];

    private static int $assertionCount = 0;

    private static float $suiteStart = 0.0;

    public static function reset(): void
    {
        self::$results        = [];
        self::$startTimes     = [];
        self::$assertionCount = 0;
        self::$suiteStart     = microtime(true);
    }

    public static function recordStart(string $testId): void
    {
        self::$startTimes[$testId] = microtime(true);
    }

    public static function getDuration(string $testId): float
    {
        return round(microtime(true) - (self::$startTimes[$testId] ?? microtime(true)), 2);
    }

    public static function add(TestResult $result): void
    {
        self::$results[] = $result;
    }

    public static function addAssertions(int $count): void
    {
        self::$assertionCount += $count;
    }

    /** @return array<int, TestResult> */
    public static function getResults(): array
    {
        return self::$results;
    }

    public static function getAssertionCount(): int
    {
        return self::$assertionCount;
    }

    public static function getTotalDuration(): float
    {
        return round(microtime(true) - self::$suiteStart, 2);
    }
}
