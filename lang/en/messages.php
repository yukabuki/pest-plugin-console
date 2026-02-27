<?php

declare(strict_types=1);

/**
 * English translations for pest-plugin-console.
 *
 * To provide your own translations, create a similar file and register it:
 *
 *   $manager->addResourcePath('/your/lang/fr/messages.php', 'fr');
 */
return [
    // General
    'tests.passed'  => 'Tests passed!',
    'tests.failed'  => 'Tests failed.',
    'tests.summary' => ':passed passed, :failed failed, :skipped skipped (:total total)',

    // Run info
    'run.start'    => 'Running test suite...',
    'run.duration' => 'Completed in :time seconds.',
];
