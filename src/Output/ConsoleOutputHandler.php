<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Output;

use Yukabuki\PestPluginConsole\Translations\TranslationManager;

use function Termwind\render;

/**
 * Handles enhanced console output using Termwind.
 *
 * @internal
 */
final class ConsoleOutputHandler
{
    public function __construct(private readonly TranslationManager $translations)
    {
    }

    /**
     * Render a success message.
     */
    public function success(string $messageKey, array $parameters = []): void
    {
        $message = $this->translations->trans($messageKey, $parameters);

        render(<<<HTML
            <div class="py-1">
                <span class="bg-green-500 text-white px-2">PASS</span>
                <span class="ml-1">{$message}</span>
            </div>
        HTML);
    }

    /**
     * Render an error message.
     */
    public function error(string $messageKey, array $parameters = []): void
    {
        $message = $this->translations->trans($messageKey, $parameters);

        render(<<<HTML
            <div class="py-1">
                <span class="bg-red-500 text-white px-2">FAIL</span>
                <span class="ml-1">{$message}</span>
            </div>
        HTML);
    }

    /**
     * Render an info message.
     */
    public function info(string $messageKey, array $parameters = []): void
    {
        $message = $this->translations->trans($messageKey, $parameters);

        render(<<<HTML
            <div class="py-1">
                <span class="bg-blue-500 text-white px-2">INFO</span>
                <span class="ml-1">{$message}</span>
            </div>
        HTML);
    }
}
