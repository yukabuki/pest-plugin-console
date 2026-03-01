<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Output;

/**
 * PHP write-filter that discards everything written to the stream.
 * Attached to STDOUT in Plugin::boot() to suppress Collision/Pest output,
 * then removed before our own ConsoleRenderer writes.
 *
 * @internal
 */
final class NullStreamFilter extends \php_user_filter
{
    public const string NAME = 'pest-plugin-console.null';

    public static function register(): void
    {
        if (! in_array(self::NAME, stream_get_filters(), true)) {
            stream_filter_register(self::NAME, self::class);
        }
    }

    /** @param resource $in @param resource $out */
    public function filter($in, $out, &$consumed, bool $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
            // Deliberately NOT added to $out → write is swallowed.
        }

        return PSFS_PASS_ON;
    }
}
