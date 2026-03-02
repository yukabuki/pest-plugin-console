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

    private static bool $htmlReport = false;

    private static string $htmlReportPath = 'tests/_output/pest/report.html';

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

    public static function enableHtmlReport(string $path): void
    {
        self::$htmlReport     = true;
        self::$htmlReportPath = $path;
    }

    public static function isHtmlReportEnabled(): bool
    {
        return self::$htmlReport;
    }

    public static function getHtmlReportPath(): string
    {
        return self::$htmlReportPath;
    }
}
