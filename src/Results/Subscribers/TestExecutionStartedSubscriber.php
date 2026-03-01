<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Results\Subscribers;

use PHPUnit\Event\TestRunner\ExecutionStarted;
use PHPUnit\Event\TestRunner\ExecutionStartedSubscriber;
use Yukabuki\PestPluginConsole\Output\ProgressState;
use Yukabuki\PestPluginConsole\Output\StreamingTestRenderer;
use Yukabuki\PestPluginConsole\PluginState;

/**
 * @internal
 */
final class TestExecutionStartedSubscriber implements ExecutionStartedSubscriber
{
    public function notify(ExecutionStarted $event): void
    {
        if (! PluginState::isEnabled()) {
            return;
        }

        ProgressState::init($event->testSuite()->count());
        StreamingTestRenderer::init();
    }
}
