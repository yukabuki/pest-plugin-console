<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole;

use Pest\Contracts\Plugins\AddsOutput;
use Pest\Contracts\Plugins\HandlesArguments;

use function Termwind\render;

/**
 * @internal
 */
final class Plugin implements HandlesArguments, AddsOutput
{
    /**
     * The CLI flag users can pass to bypass the plugin output entirely.
     */
    private const string FLAG = '--no-console';

    /**
     * Intercept CLI arguments before Pest processes them.
     *
     * If --no-console is present, the plugin disables itself and removes
     * the flag so Pest does not complain about an unknown option.
     *
     * @param  array<int, string>  $originals
     * @return array<int, string>
     */
    public function handleArguments(array $originals): array
    {
        if (! in_array(self::FLAG, $originals, true)) {
            return $originals;
        }

        PluginState::disable();

        return array_values(
            array_filter($originals, fn (string $arg): bool => $arg !== self::FLAG)
        );
    }

    /**
     * Adds a Termwind-rendered banner after the test suite completes.
     *
     * Skipped entirely when the user passed --no-console.
     */
    public function addOutput(int $exitCode): int
    {
        if (! PluginState::isEnabled()) {
            return $exitCode;
        }

        if ($exitCode === 0) {
            render(<<<'HTML'
                <div class="mt-1 px-2 py-1 bg-green-600 text-white font-bold">
                    ✓ All tests passed!
                </div>
            HTML);
        } else {
            render(<<<'HTML'
                <div class="mt-1 px-2 py-1 bg-red-600 text-white font-bold">
                    ✗ Some tests failed.
                </div>
            HTML);
        }

        return $exitCode;
    }
}
