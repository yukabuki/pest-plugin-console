<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Output;

use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Terminal;
use Yukabuki\PestPluginConsole\Results\TestResult;

/**
 * Writes test results to STDERR in real-time, one class at a time.
 *
 * Results are buffered per class and flushed when the class changes,
 * so the class badge (PASS/FAIL/WARN) can reflect the final state.
 *
 * @internal
 */
final class StreamingTestRenderer
{
    private static ?string $currentClass = null;

    /** @var list<TestResult> */
    private static array $buffer = [];

    private static ?StreamOutput $output = null;

    /**
     * Called once before tests start. Prints the TESTS section header to STDERR.
     */
    public static function init(): void
    {
        self::$currentClass = null;
        self::$buffer       = [];
        self::$output       = new StreamOutput(STDERR, decorated: true);

        $width = (new Terminal())->getWidth();

        ProgressState::hide();
        self::$output->writeln('');
        self::$output->writeln(' <fg=yellow;options=bold>TESTS</>');
        self::$output->writeln(sprintf('<fg=yellow>%s</>', str_repeat('─', $width)));
        self::$output->writeln('');
        ProgressState::show();
    }

    /**
     * Buffers a test result and flushes the previous class when the class changes.
     */
    public static function addResult(TestResult $result): void
    {
        if (self::$output === null) {
            return;
        }

        if ($result->className !== self::$currentClass) {
            self::flushClass();
            self::$currentClass = $result->className;
        }

        self::$buffer[] = $result;
    }

    /**
     * Flushes the last buffered class. Called from Plugin::addOutput().
     */
    public static function flush(): void
    {
        self::flushClass();
        self::$currentClass = null;
        self::$buffer       = [];
        self::$output       = null;
    }

    /**
     * Prints the buffered class header + test lines to STDERR, then clears the buffer.
     */
    private static function flushClass(): void
    {
        if (self::$buffer === [] || self::$output === null) {
            return;
        }

        $classResults = self::$buffer;
        $hasFailure   = array_filter($classResults, fn (TestResult $r): bool => $r->status === 'failed') !== [];
        $hasSkipped   = array_filter($classResults, fn (TestResult $r): bool => $r->status === 'skipped') !== [];

        [$badge, $badgeFg, $badgeBg] = match (true) {
            $hasFailure => ['FAIL', 'white', 'red'],
            $hasSkipped => ['WARN', 'black', 'yellow'],
            default     => ['PASS', 'white', 'green'],
        };

        $parts     = explode('\\', $classResults[0]->className);
        $className = end($parts).'.php';

        ProgressState::hide();

        self::$output->writeln(sprintf(
            ' <fg=%s;bg=%s;options=bold> %s </> <options=bold>%s</>',
            $badgeFg,
            $badgeBg,
            $badge,
            $className,
        ));

        foreach ($classResults as $result) {
            [$icon, $iconColor] = match ($result->status) {
                'passed'  => ['✓', 'green'],
                'failed'  => ['⨯', 'red'],
                'skipped' => ['-', 'yellow'],
                default   => ['?', 'gray'],
            };

            $duration = $result->duration > 0
                ? sprintf(' <fg=gray>%ss</>', number_format($result->duration, 2))
                : '';

            self::$output->writeln(sprintf(
                '   <fg=%s>%s</> %s%s',
                $iconColor,
                $icon,
                $result->testName,
                $duration,
            ));
        }

        self::$output->writeln('');

        ProgressState::show();

        self::$buffer = [];
    }
}
