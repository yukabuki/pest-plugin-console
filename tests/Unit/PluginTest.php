<?php

declare(strict_types=1);

use Yukabuki\PestPluginConsole\Plugin;
use Yukabuki\PestPluginConsole\PluginState;

it('leaves arguments untouched when --no-console is absent', function (): void {
    $plugin = new Plugin();
    $args = ['--colors=always', '--verbose'];

    expect($plugin->handleArguments($args))->toBe($args);
});

it('removes --no-console from arguments and disables the plugin', function (): void {
    $plugin = new Plugin();
    $args = ['--colors=always', '--no-console', '--verbose'];

    $result = $plugin->handleArguments($args);

    expect($result)
        ->not->toContain('--no-console')
        ->toContain('--colors=always')
        ->toContain('--verbose');

    expect(PluginState::isEnabled())->toBeFalse();
});
