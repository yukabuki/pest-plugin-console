<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Results;

use PHPUnit\Event\Code\Throwable;

/**
 * @internal
 */
final class FailureDetails
{
    /** @param array<int, string> $codeLines  line_number => line_content */
    public function __construct(
        public readonly string $message,
        public readonly string $file,
        public readonly int $line,
        public readonly array $codeLines,
    ) {}

    public static function fromThrowable(Throwable $throwable): self
    {
        // PHPUnit's stackTrace() format (vendor-filtered): "file:line\n" per frame.
        // The first frame is the exact location of the failure in user code.
        [$file, $line] = self::parseFirstFrame($throwable->stackTrace());

        return new self(
            message: $throwable->message(),
            file: $file,
            line: $line,
            codeLines: ($file !== '' && $line > 0) ? self::extractLines($file, $line) : [],
        );
    }

    /** @return array{string, int} */
    private static function parseFirstFrame(string $stackTrace): array
    {
        foreach (explode("\n", trim($stackTrace)) as $frame) {
            $frame = trim($frame);
            if ($frame === '') {
                continue;
            }
            // Format: /absolute/path/to/file.php:42
            if (preg_match('/^(.+):(\d+)$/', $frame, $m) === 1) {
                return [$m[1], (int) $m[2]];
            }
        }

        return ['', 0];
    }

    /** @return array<int, string> */
    private static function extractLines(string $file, int $errorLine): array
    {
        if (! is_file($file)) {
            return [];
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }

        $start = max(0, $errorLine - 4);
        $end   = min(count($lines) - 1, $errorLine + 1);

        $extracted = [];
        for ($i = $start; $i <= $end; $i++) {
            $extracted[$i + 1] = $lines[$i]; // 1-based line numbers
        }

        return $extracted;
    }
}
