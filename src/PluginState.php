<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole;

/**
 * Holds the global enabled/disabled state of the plugin.
 *
 * Disabled when the user passes --no-console to the pest command.
 *
 * @internal
 */
final class PluginState
{
    private static bool $enabled = true;

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }
}
