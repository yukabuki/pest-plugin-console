<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Results\Subscribers;

use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use Yukabuki\PestPluginConsole\Results\TestResultCollector;

/**
 * @internal
 */
final class TestFinishedSubscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        TestResultCollector::addAssertions($event->numberOfAssertionsPerformed());
    }
}
