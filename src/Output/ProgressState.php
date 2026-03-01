<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Output;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @internal
 */
final class ProgressState
{
    private static ?ProgressBar $bar = null;

    public static function init(int $total): void
    {
        if ($total === 0) {
            return;
        }

        $output = new StreamOutput(STDERR, decorated: true);

        $bar = new ProgressBar($output, $total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %elapsed:6s%');
        $bar->setBarCharacter('<fg=green>━</>');
        $bar->setEmptyBarCharacter('<fg=gray>─</>');
        $bar->setProgressCharacter('<fg=green>╸</>');
        $bar->start();

        self::$bar = $bar;
    }

    public static function advance(): void
    {
        self::$bar?->advance();
    }

    public static function clear(): void
    {
        if (self::$bar !== null) {
            self::$bar->clear();
            self::$bar = null;
        }
    }
}
