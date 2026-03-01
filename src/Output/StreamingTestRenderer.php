<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Output;

use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Terminal;
use Yukabuki\PestPluginConsole\Results\TestResult;
use Yukabuki\PestPluginConsole\Translations\TranslationManager;

/**
 * Writes test results to STDERR in real-time.
 *
 * Each test line is printed immediately when it arrives.
 * A class header is printed the first time a test from that class is seen.
 * The PASS/FAIL/WARN badge is printed after the last test of a class,
 * when the next class starts (or at flush).
 *
 * @internal
 */
final class StreamingTestRenderer
{
    private static ?string $currentClass = null;

    private static bool $classHasFailure = false;

    private static bool $classHasSkipped = false;

    private static ?StreamOutput $output = null;

    public static function init(): void
    {
        self::$currentClass   = null;
        self::$classHasFailure = false;
        self::$classHasSkipped = false;
        self::$output          = new StreamOutput(STDERR, decorated: true);

        $width = (new Terminal())->getWidth();

        ProgressState::hide();
        self::$output->writeln('');
        self::$output->writeln(sprintf(' <fg=yellow;options=bold>%s</>', TranslationManager::get('section.tests')));
        self::$output->writeln(sprintf('<fg=yellow>%s</>', str_repeat('─', $width)));
        self::$output->writeln('');
        ProgressState::show();
    }

    public static function addResult(TestResult $result): void
    {
        if (self::$output === null) {
            return;
        }

        // Class changed → flush badge for the previous class, then print new class header
        if ($result->className !== self::$currentClass) {
            self::printBadge();

            $parts     = explode('\\', $result->className);
            $className = end($parts).'.php';

            ProgressState::hide();

            if (self::$currentClass !== null) {
                self::$output->writeln('');
            }

            self::$output->writeln(sprintf(' <options=bold>%s</>', $className));
            ProgressState::show();

            self::$currentClass    = $result->className;
            self::$classHasFailure = false;
            self::$classHasSkipped = false;
        }

        // Track class outcome for the badge
        if ($result->status === 'failed') {
            self::$classHasFailure = true;
        } elseif ($result->status === 'skipped') {
            self::$classHasSkipped = true;
        }

        // Print test line immediately
        [$icon, $iconColor] = match ($result->status) {
            'passed'  => ['✓', 'green'],
            'failed'  => ['⨯', 'red'],
            'skipped' => ['-', 'yellow'],
            default   => ['?', 'gray'],
        };

        $duration = $result->duration > 0
            ? sprintf(' <fg=gray>%ss</>', number_format($result->duration, 2))
            : '';

        ProgressState::hide();
        self::$output->writeln(sprintf(
            '   <fg=%s>%s</> %s%s',
            $iconColor,
            $icon,
            $result->testName,
            $duration,
        ));
        ProgressState::show();
    }

    public static function flush(): void
    {
        self::printBadge();
        self::$currentClass    = null;
        self::$classHasFailure = false;
        self::$classHasSkipped = false;
        self::$output          = null;
    }

    /**
     * Prints the PASS/FAIL/WARN badge below the last test of the current class.
     */
    private static function printBadge(): void
    {
        if (self::$currentClass === null || self::$output === null) {
            return;
        }

        [$badge, $badgeFg, $badgeBg] = match (true) {
            self::$classHasFailure => [TranslationManager::get('badge.fail'), 'white', 'red'],
            self::$classHasSkipped => [TranslationManager::get('badge.warn'), 'black', 'yellow'],
            default                => [TranslationManager::get('badge.pass'), 'white', 'green'],
        };

        ProgressState::hide();
        self::$output->writeln(sprintf(
            ' <fg=%s;bg=%s;options=bold> %s </>',
            $badgeFg,
            $badgeBg,
            $badge,
        ));
        ProgressState::show();
    }
}
