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

    private static bool $slow = false;

    private static string $locale = 'en';

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    public static function enableSlow(): void
    {
        self::$slow = true;
    }

    public static function isSlow(): bool
    {
        return self::$slow;
    }

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }
}
