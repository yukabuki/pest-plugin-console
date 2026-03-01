<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Output;

use Yukabuki\PestPluginConsole\Results\TestResult;

use function Termwind\render;
use function Termwind\terminal;

/**
 * @internal
 */
final class ConsoleRenderer
{
    /** @param array<int, TestResult> $results */
    public function render(array $results, float $duration, int $assertionCount): void
    {
        $this->renderTestsByClass($results);

        $failures = array_values(array_filter($results, fn (TestResult $r): bool => $r->status === 'failed'));

        if ($failures !== []) {
            $this->renderFailuresSection($failures);
        }

        $this->renderSummary($results, $duration, $assertionCount);
    }

    // -------------------------------------------------------------------------

    /** @param array<int, TestResult> $results */
    private function renderTestsByClass(array $results): void
    {
        render(<<<'HTML'
            <div class="mt-1 ml-1">
                <span class="font-bold text-gray-400 uppercase">Tests</span>
            </div>
        HTML);

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
        $separator = str_repeat('─', min(terminal()->width(), 80));

        render(<<<HTML
            <div class="mt-1 text-gray-600">{$separator}</div>
            <div class="mt-1 ml-1">
                <span class="font-bold text-red-500 uppercase">Fail</span>
            </div>
        HTML);

        foreach ($failures as $i => $failure) {
            $this->renderFailureDetail($i + 1, $failure);
        }

        render(<<<HTML
            <div class="mt-1 text-gray-600">{$separator}</div>
        HTML);
    }

    private function renderFailureDetail(int $number, TestResult $result): void
    {
        $failure  = $result->failure;
        $title    = htmlspecialchars("{$result->displayClass} › {$result->testName}", ENT_QUOTES);
        $message  = htmlspecialchars($failure?->message ?? '', ENT_QUOTES);
        $location = '';

        if ($failure !== null) {
            $cwd      = rtrim((string) getcwd(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            $relFile  = str_replace($cwd, '', $failure->file);
            $relFile  = str_replace('\\', '/', $relFile);
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

            // Preserve indentation by converting leading whitespace to &nbsp;
            $expanded = str_replace("\t", '    ', $content);
            $trimmed  = ltrim($expanded);
            $spaces   = strlen($expanded) - strlen($trimmed);
            $indent   = str_repeat('&nbsp;', $spaces);
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
    private function renderSummary(array $results, float $duration, int $assertionCount): void
    {
        $separator = str_repeat('─', min(terminal()->width(), 80));

        render(<<<HTML
            <div class="mt-1 text-gray-600">{$separator}</div>
            <div class="ml-1">
                <span class="font-bold text-gray-400 uppercase">Report</span>
            </div>
        HTML);

        $passed  = count(array_filter($results, fn (TestResult $r): bool => $r->status === 'passed'));
        $failed  = count(array_filter($results, fn (TestResult $r): bool => $r->status === 'failed'));
        $skipped = count(array_filter($results, fn (TestResult $r): bool => $r->status === 'skipped'));

        $total = count($results);
        $avg   = $total > 0 ? $duration / $total : 0.0;

        $headers = ['Passed', 'Failed', 'Skipped', 'Total', 'Assertions', 'Duration', 'Avg'];
        $values  = [
            (string) $passed,
            (string) $failed,
            (string) $skipped,
            (string) $total,
            (string) $assertionCount,
            number_format($duration, 2).'s',
            number_format($avg, 2).'s',
        ];

        // Column widths = max(header length, value length), min 6
        $widths = array_map(
            static fn (string $h, string $v): int => max(strlen($h), strlen($v), 6),
            $headers,
            $values,
        );

        $pad = static fn (string $s, int $w): string => str_pad($s, $w, ' ', STR_PAD_BOTH);

        $segs = static fn (string $fill): array => array_map(
            static fn (int $w): string => str_repeat($fill, $w + 2),
            $widths,
        );

        $top  = '┌'.implode('┬', $segs('─')).'┐';
        $mid  = '├'.implode('┼', $segs('─')).'┤';
        $bot  = '└'.implode('┴', $segs('─')).'┘';
        $hRow = '│'.implode('│', array_map(static fn (string $h, int $w): string => ' '.$pad($h, $w).' ', $headers, $widths)).'│';

        // Color per column — Passed/Failed/Skipped only get a background when count > 0
        $colColors = [
            $passed  > 0 ? 'bg-green-700 text-white'  : 'text-gray-300',
            $failed  > 0 ? 'bg-red-700 text-white'    : 'text-gray-300',
            $skipped > 0 ? 'bg-yellow-600 text-black'  : 'text-gray-300',
            'bg-gray-700 text-white',   // Total — always
            'bg-blue-700 text-white',   // Assertions — always
            'text-gray-300',            // Duration — no background
            'text-gray-300',            // Avg — no background
        ];

        // Value row: each cell is a colored span, separators are white
        $vHtml = '<span class="text-white">│</span>';
        foreach ($values as $i => $v) {
            $cell   = str_replace(' ', '&nbsp;', htmlspecialchars(' '.$pad($v, $widths[$i]).' ', ENT_QUOTES));
            $color  = $colColors[$i] ?? 'text-gray-300';
            $vHtml .= '<span class="'.$color.'">'.$cell.'</span><span class="text-white">│</span>';
        }

        // Render border rows as plain text (white), header row as gray
        $html = '';
        foreach ([[$top, 'text-white'], [$hRow, 'text-gray-400'], [$mid, 'text-white']] as [$text, $class]) {
            $safe  = str_replace(' ', '&nbsp;', htmlspecialchars($text, ENT_QUOTES));
            $html .= "<div class=\"ml-1 {$class}\">{$safe}</div>";
        }
        $html .= "<div class=\"ml-1 flex\">{$vHtml}</div>";
        $safe  = str_replace(' ', '&nbsp;', htmlspecialchars($bot, ENT_QUOTES));
        $html .= "<div class=\"ml-1 text-white\">{$safe}</div>";

        render('<div class="mt-1">'.$html.'</div>');
    }
}
