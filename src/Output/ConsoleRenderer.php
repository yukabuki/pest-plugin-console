<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Output;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Yukabuki\PestPluginConsole\Results\TestResult;
use Yukabuki\PestPluginConsole\Translations\TranslationManager;

use function Termwind\render;

/**
 * @internal
 */
final class ConsoleRenderer
{
    /** @param array<int, TestResult> $results */
    public function render(array $results, float $duration, int $assertionCount): void
    {
        $io = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput(decorated: true));

        $failures = array_values(array_filter($results, fn (TestResult $r): bool => $r->status === 'failed'));

        if ($failures !== []) {
            $this->renderSection($io, TranslationManager::get('section.fail'));
            $this->renderFailuresSection($failures);
        }

        $this->renderSummary($results, $duration, $assertionCount, $io);
    }

    // -------------------------------------------------------------------------

    private function renderSection(SymfonyStyle $io, string $title): void
    {
        $width = (new Terminal())->getWidth();
        $io->newLine();
        $io->writeln(sprintf(' <fg=yellow;options=bold>%s</>', strtoupper($title)));
        $io->writeln(sprintf('<fg=yellow>%s</>', str_repeat('─', $width)));
        $io->newLine();
    }

    // -------------------------------------------------------------------------

    /** @param array<int, TestResult> $results */
    private function renderTestsByClass(array $results, SymfonyStyle $io): void
    {
        $this->renderSection($io, 'Tests');

        /** @var array<string, list<TestResult>> $byClass */
        $byClass = [];

        foreach ($results as $result) {
            $byClass[$result->className][] = $result;
        }

        foreach ($byClass as $classResults) {
            $parts     = explode('\\', $classResults[0]->className);
            $safeClass = htmlspecialchars(end($parts).'.php', ENT_QUOTES);

            $hasFailure = array_filter($classResults, fn (TestResult $r): bool => $r->status === 'failed') !== [];
            $hasSkipped = array_filter($classResults, fn (TestResult $r): bool => $r->status === 'skipped') !== [];

            [$badge, $badgeClass] = match (true) {
                $hasFailure => ['FAIL', 'bg-red-700 text-white font-bold'],
                $hasSkipped => ['WARN', 'bg-yellow-600 text-black font-bold'],
                default     => ['PASS', 'bg-green-700 text-white font-bold'],
            };

            render(<<<HTML
                <div class="mt-1 ml-1 flex">
                    <span class="{$badgeClass}">&nbsp;{$badge}&nbsp;</span>
                    <span class="ml-2 font-bold text-gray-300">{$safeClass}</span>
                </div>
            HTML);

            foreach ($classResults as $result) {
                $this->renderTestLine($result);
            }
        }
    }

    private function renderTestLine(TestResult $result): void
    {
        [$icon, $iconClass, $nameClass] = match ($result->status) {
            'passed'  => ['✓', 'text-green-400', 'text-gray-300'],
            'failed'  => ['⨯', 'text-red-400',   'text-gray-300'],
            'skipped' => ['-', 'text-yellow-400', 'text-gray-300'],
            default   => ['?', 'text-gray-400',   'text-gray-300'],
        };

        $name     = htmlspecialchars($result->testName, ENT_QUOTES);
        $duration = $result->duration > 0 ? number_format($result->duration, 2).'s' : '';

        render(<<<HTML
            <div class="flex ml-3">
                <span class="{$iconClass} w-2">{$icon}</span>
                <span class="ml-1 flex-1 {$nameClass}">{$name}</span>
                <span class="text-gray-500 ml-2">{$duration}</span>
            </div>
        HTML);
    }

    // -------------------------------------------------------------------------

    /** @param array<int, TestResult> $failures */
    private function renderFailuresSection(array $failures): void
    {
        foreach ($failures as $i => $failure) {
            $this->renderFailureDetail($i + 1, $failure);
        }
    }

    private function renderFailureDetail(int $number, TestResult $result): void
    {
        $failure  = $result->failure;
        $title    = htmlspecialchars("{$result->displayClass} › {$result->testName}", ENT_QUOTES);
        $message  = htmlspecialchars($failure?->message ?? '', ENT_QUOTES);
        $location = '';

        if ($failure !== null) {
            $cwd      = rtrim((string) getcwd(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            $relFile  = str_replace(['\\', $cwd], ['/', ''], $failure->file);
            $location = htmlspecialchars("at {$relFile}:{$failure->line}", ENT_QUOTES);
        }

        render(<<<HTML
            <div class="mt-1 ml-2">
                <span class="text-red-400 font-bold">{$number}) {$title}</span>
                <div class="ml-2 mt-1 text-red-300">{$message}</div>
                <div class="ml-2 text-gray-500">{$location}</div>
            </div>
        HTML);

        if ($failure !== null && $failure->codeLines !== []) {
            $this->renderCodeSnippet($failure->codeLines, $failure->line);
        }
    }

    /**
     * @param array<int, string> $lines  line_number => content
     */
    private function renderCodeSnippet(array $lines, int $errorLine): void
    {
        $linesHtml = '';

        foreach ($lines as $lineNumber => $content) {
            $isError   = $lineNumber === $errorLine;
            $lineClass = $isError ? 'text-white bg-red-900' : 'text-gray-400';
            $arrow     = $isError
                ? '<span class="text-red-400 font-bold">➜</span>'
                : '<span class="text-gray-600"> </span>';
            $num       = htmlspecialchars(str_pad((string) $lineNumber, 4, ' ', STR_PAD_LEFT), ENT_QUOTES);

            $expanded = str_replace("\t", '    ', $content);
            $trimmed  = ltrim($expanded);
            $indent   = str_repeat('&nbsp;', strlen($expanded) - strlen($trimmed));
            $code     = $indent.htmlspecialchars(rtrim($trimmed), ENT_QUOTES);

            $linesHtml .= <<<HTML
                <div class="flex {$lineClass}">
                    <span class="mr-1">{$arrow}</span>
                    <span class="text-gray-500 mr-1">{$num}</span>
                    <span class="text-gray-500">│</span>
                    <span class="ml-1">{$code}</span>
                </div>
            HTML;
        }

        render(<<<HTML
            <div class="ml-4 mt-1 mb-1">
                {$linesHtml}
            </div>
        HTML);
    }

    // -------------------------------------------------------------------------

    /** @param array<int, TestResult> $results */
    private function renderSummary(array $results, float $duration, int $assertionCount, SymfonyStyle $io): void
    {
        $this->renderSection($io, TranslationManager::get('section.report'));

        $passed  = count(array_filter($results, fn (TestResult $r): bool => $r->status === 'passed'));
        $failed  = count(array_filter($results, fn (TestResult $r): bool => $r->status === 'failed'));
        $skipped = count(array_filter($results, fn (TestResult $r): bool => $r->status === 'skipped'));
        $total   = count($results);
        $avg     = $total > 0 ? $duration / $total : 0.0;

        $row = [
            $passed  > 0 ? "<fg=white;bg=green;options=bold> {$passed} </>"   : " {$passed} ",
            $failed  > 0 ? "<fg=white;bg=red;options=bold> {$failed} </>"     : " {$failed} ",
            $skipped > 0 ? "<fg=black;bg=yellow;options=bold> {$skipped} </>" : " {$skipped} ",
            "<fg=white;bg=gray;options=bold> {$total} </>",
            "<fg=white;bg=blue;options=bold> {$assertionCount} </>",
            ' '.number_format($duration, 2).'s ',
            ' '.number_format($avg, 2).'s ',
        ];

        $table = new Table($io);
        $table->setStyle('box');
        $table->setHeaders([
            TranslationManager::get('table.passed'),
            TranslationManager::get('table.failed'),
            TranslationManager::get('table.skipped'),
            TranslationManager::get('table.total'),
            TranslationManager::get('table.assertions'),
            TranslationManager::get('table.duration'),
            TranslationManager::get('table.avg'),
        ]);
        $table->addRow($row);
        $table->render();
    }
}
