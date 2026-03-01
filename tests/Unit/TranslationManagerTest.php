<?php

declare(strict_types=1);

use Yukabuki\PestPluginConsole\Translations\TranslationManager;

it('translates a key using the default English locale', function (): void {
    $manager = new TranslationManager();

    expect($manager->trans('section.report'))->toBe('REPORT');
});

it('switches locale at runtime', function (): void {
    $manager = new TranslationManager();
    $manager->setLocale('fr');

    expect($manager->getLocale())->toBe('fr');
});

it('accepts custom translation resource paths', function (): void {
    $manager = new TranslationManager();

    $tmpFile = tempnam(sys_get_temp_dir(), 'pest_plugin_console_').'.php';
    file_put_contents($tmpFile, '<?php return ["table.avg" => "Moyenne"];');

    $manager->addResourcePath($tmpFile, 'fr');
    $manager->setLocale('fr');

    expect($manager->trans('table.avg'))->toBe('Moyenne');

    unlink($tmpFile);
});
