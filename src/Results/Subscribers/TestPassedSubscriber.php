<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Results\Subscribers;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;
use Yukabuki\PestPluginConsole\Results\TestResult;
use Yukabuki\PestPluginConsole\Results\TestResultCollector;

/**
 * @internal
 */
final class TestPassedSubscriber implements PassedSubscriber
{
    public function notify(Passed $event): void
    {
        $test = $event->test();

        TestResultCollector::add(new TestResult(
            className: $test instanceof TestMethod ? $test->className() : explode('::', $test->id())[0],
            displayClass: $test instanceof TestMethod ? $test->testDox()->prettifiedClassName() : explode('::', $test->id())[0],
            testName: $test instanceof TestMethod ? $test->testDox()->prettifiedMethodName() : $test->name(),
            status: 'passed',
            duration: TestResultCollector::getDuration($test->id()),
        ));
    }
}
