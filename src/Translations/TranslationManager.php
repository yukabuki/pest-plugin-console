<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Translations;

use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;
use Yukabuki\PestPluginConsole\PluginState;

/**
 * Manages translations for the plugin output.
 *
 * Users can extend the available locales by calling `addResourcePath()`.
 *
 * @internal
 */
final class TranslationManager
{
    private static ?self $instance = null;

    private readonly Translator $translator;

    public function __construct(private string $locale = 'en')
    {
        $this->translator = new Translator($this->locale);
        $this->translator->addLoader('php', new PhpFileLoader());

        $this->registerBuiltinTranslations();
    }

    /**
     * Returns the shared singleton, instantiated with the current PluginState locale.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(PluginState::getLocale());
        }

        return self::$instance;
    }

    /**
     * Static shortcut: translate a key using the shared singleton.
     *
     * @param array<string, string> $parameters
     */
    public static function get(string $id, array $parameters = []): string
    {
        return self::instance()->trans($id, $parameters);
    }

    /**
     * Translate a message key.
     *
     * @param array<string, string> $parameters
     */
    public function trans(string $id, array $parameters = [], ?string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, 'messages', $locale ?? $this->locale);
    }

    /**
     * Add a custom PHP translation file for a given locale.
     *
     * The file must return an associative array of translation keys and values.
     *
     * Example usage in user code:
     *
     *   $manager->addResourcePath('/path/to/lang/fr/messages.php', 'fr');
     */
    public function addResourcePath(string $path, string $locale): self
    {
        $this->translator->addResource('php', $path, $locale, 'messages');

        return $this;
    }

    /**
     * Change the active locale at runtime.
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        $this->translator->setLocale($locale);

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    private function registerBuiltinTranslations(): void
    {
        $langDir = dirname(__DIR__, 2).'/lang';

        foreach (glob($langDir.'/*/messages.php') ?: [] as $file) {
            $locale = basename(dirname($file));
            $this->translator->addResource('php', $file, $locale, 'messages');
        }
    }
}
