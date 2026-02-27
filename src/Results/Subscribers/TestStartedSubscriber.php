<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Results\Subscribers;

use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;
use Yukabuki\PestPluginConsole\Results\TestResultCollector;

/**
 * @internal
 */
final class TestStartedSubscriber implements PreparedSubscriber
{
    public function notify(Prepared $event): void
    {
        TestResultCollector::recordStart($event->test()->id());
    }
}
