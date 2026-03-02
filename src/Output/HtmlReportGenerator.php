<?php

declare(strict_types=1);

namespace Yukabuki\PestPluginConsole\Output;

use Yukabuki\PestPluginConsole\Results\TestResult;

/**
 * @internal
 */
final class HtmlReportGenerator
{
    /** @param array<int, TestResult> $results */
    public function generate(string $path, array $results, float $duration, int $assertionCount): void
    {
        $html = $this->buildHtml($results, $duration, $assertionCount);
        $this->writeFile($path, $html);
    }

    // -------------------------------------------------------------------------

    /** @param array<int, TestResult> $results */
    private function buildHtml(array $results, float $duration, int $assertionCount): string
    {
        $failures = array_values(array_filter($results, fn (TestResult $r): bool => $r->status === 'failed'));

        $passed  = count(array_filter($results, fn (TestResult $r): bool => $r->status === 'passed'));
        $failed  = count($failures);
        $skipped = count(array_filter($results, fn (TestResult $r): bool => $r->status === 'skipped'));
        $total   = count($results);

        $summaryBar      = $this->buildSummaryBar($passed, $failed, $skipped, $total, $assertionCount, $duration);
        $classGroups     = $this->buildClassGroups($results);
        $failuresSection = $failures !== [] ? $this->buildFailuresSection($failures) : '';
        $css             = $this->buildCss();
        $title           = $this->e('Pest Report');
        $generatedAt     = $this->e(date('Y-m-d H:i:s'));

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <style>{$css}</style>
        </head>
        <body>
            <header>
                <h1>Pest Test Report</h1>
                <p class="generated-at">Generated at {$generatedAt}</p>
            </header>
            <main>
                {$summaryBar}
                {$failuresSection}
                {$classGroups}
            </main>
        </body>
        </html>
        HTML;
    }

    // -------------------------------------------------------------------------

    private function buildCss(): string
    {
        return <<<'CSS'
        :root {
            --c-pass: #22c55e;
            --c-fail: #ef4444;
            --c-skip: #eab308;
            --c-bg:   #0f172a;
            --c-surface: #1e293b;
            --c-border:  #334155;
            --c-text:    #e2e8f0;
            --c-muted:   #94a3b8;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--c-bg);
            color: var(--c-text);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 14px;
            line-height: 1.6;
            padding: 2rem;
        }
        header { margin-bottom: 2rem; }
        header h1 { font-size: 1.5rem; font-weight: 700; color: #f8fafc; }
        .generated-at { color: var(--c-muted); font-size: 0.8rem; margin-top: 0.25rem; }

        /* Summary bar */
        .summary-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        .stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.75rem 1.25rem;
            border-radius: 6px;
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            min-width: 90px;
        }
        .stat-value { font-size: 1.5rem; font-weight: 700; }
        .stat-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--c-muted); margin-top: 0.2rem; }
        .stat.pass .stat-value { color: var(--c-pass); }
        .stat.fail .stat-value { color: var(--c-fail); }
        .stat.skip .stat-value { color: var(--c-skip); }
        .stat.total .stat-value,
        .stat.assertions .stat-value,
        .stat.duration .stat-value { color: var(--c-text); }

        /* Section heading */
        .section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--c-muted);
            border-bottom: 1px solid var(--c-border);
            padding-bottom: 0.4rem;
            margin-bottom: 1rem;
        }
        section { margin-bottom: 2rem; }

        /* Class groups */
        .class-group { margin-bottom: 1rem; }
        .class-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.25rem;
        }
        .badge {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            padding: 0.1rem 0.45rem;
            border-radius: 3px;
        }
        .badge-pass { background: #166534; color: #bbf7d0; }
        .badge-fail { background: #7f1d1d; color: #fecaca; }
        .badge-warn { background: #713f12; color: #fef08a; }
        .class-name { font-weight: 600; color: #cbd5e1; }

        .test-line {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            padding: 0.1rem 0 0.1rem 1.5rem;
        }
        .test-icon-pass { color: var(--c-pass); }
        .test-icon-fail { color: var(--c-fail); }
        .test-icon-skip { color: var(--c-skip); }
        .test-icon-unknown { color: var(--c-muted); }
        .test-name { flex: 1; color: #cbd5e1; }
        .test-duration { color: var(--c-muted); font-size: 0.8rem; }

        /* Failures */
        .failure-card {
            background: var(--c-surface);
            border: 1px solid #7f1d1d;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .failure-title { color: var(--c-fail); font-weight: 700; margin-bottom: 0.5rem; }
        .failure-message { color: #fca5a5; margin-bottom: 0.4rem; white-space: pre-wrap; }
        .failure-location { color: var(--c-muted); font-size: 0.8rem; margin-bottom: 0.75rem; }

        /* Code snippet */
        .code-snippet { border-radius: 4px; overflow: hidden; }
        .code-line {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            padding: 0.1rem 0.5rem;
        }
        .code-line.error-line { background: #450a0a; }
        .code-arrow-err { color: var(--c-fail); font-weight: 700; width: 1rem; text-align: right; flex-shrink: 0; }
        .code-arrow-ok  { color: transparent; width: 1rem; text-align: right; flex-shrink: 0; }
        .code-lineno { color: var(--c-muted); min-width: 3rem; text-align: right; flex-shrink: 0; }
        .code-sep { color: var(--c-border); }
        .code-content { color: #d1d5db; white-space: pre; }
        .code-line.error-line .code-content { color: #f9fafb; }
        CSS;
    }

    // -------------------------------------------------------------------------

    private function buildSummaryBar(
        int $passed,
        int $failed,
        int $skipped,
        int $total,
        int $assertionCount,
        float $duration,
    ): string {
        $durationFmt = number_format($duration, 2).'s';

        return <<<HTML
        <div class="summary-bar">
            <div class="stat pass">
                <span class="stat-value">{$passed}</span>
                <span class="stat-label">Passed</span>
            </div>
            <div class="stat fail">
                <span class="stat-value">{$failed}</span>
                <span class="stat-label">Failed</span>
            </div>
            <div class="stat skip">
                <span class="stat-value">{$skipped}</span>
                <span class="stat-label">Skipped</span>
            </div>
            <div class="stat total">
                <span class="stat-value">{$total}</span>
                <span class="stat-label">Total</span>
            </div>
            <div class="stat assertions">
                <span class="stat-value">{$assertionCount}</span>
                <span class="stat-label">Assertions</span>
            </div>
            <div class="stat duration">
                <span class="stat-value">{$durationFmt}</span>
                <span class="stat-label">Duration</span>
            </div>
        </div>
        HTML;
    }

    // -------------------------------------------------------------------------

    /** @param array<int, TestResult> $results */
    private function buildClassGroups(array $results): string
    {
        /** @var array<string, list<TestResult>> $byClass */
        $byClass = [];

        foreach ($results as $result) {
            $byClass[$result->className][] = $result;
        }

        $html = '<section><p class="section-title">Tests</p>';

        foreach ($byClass as $classResults) {
            $parts     = explode('\\', $classResults[0]->className);
            $className = $this->e(end($parts).'.php');

            $hasFailure = array_filter($classResults, fn (TestResult $r): bool => $r->status === 'failed') !== [];
            $hasSkipped = array_filter($classResults, fn (TestResult $r): bool => $r->status === 'skipped') !== [];

            [$badgeText, $badgeClass] = match (true) {
                $hasFailure => ['FAIL', 'badge-fail'],
                $hasSkipped => ['WARN', 'badge-warn'],
                default     => ['PASS', 'badge-pass'],
            };

            $html .= <<<HTML
            <div class="class-group">
                <div class="class-header">
                    <span class="badge {$badgeClass}">{$badgeText}</span>
                    <span class="class-name">{$className}</span>
                </div>
            HTML;

            foreach ($classResults as $result) {
                [$icon, $iconClass] = match ($result->status) {
                    'passed'  => ['✓', 'test-icon-pass'],
                    'failed'  => ['⨯', 'test-icon-fail'],
                    'skipped' => ['-', 'test-icon-skip'],
                    default   => ['?', 'test-icon-unknown'],
                };

                $name     = $this->e($result->testName);
                $duration = $result->duration > 0 ? $this->e(number_format($result->duration, 2).'s') : '';

                $html .= <<<HTML
                <div class="test-line">
                    <span class="{$iconClass}">{$icon}</span>
                    <span class="test-name">{$name}</span>
                    <span class="test-duration">{$duration}</span>
                </div>
                HTML;
            }

            $html .= '</div>';
        }

        $html .= '</section>';

        return $html;
    }

    // -------------------------------------------------------------------------

    /** @param array<int, TestResult> $failures */
    private function buildFailuresSection(array $failures): string
    {
        $html = '<section><p class="section-title">Failures</p>';

        foreach ($failures as $i => $result) {
            $html .= $this->buildFailureCard($i + 1, $result);
        }

        $html .= '</section>';

        return $html;
    }

    private function buildFailureCard(int $number, TestResult $result): string
    {
        $failure  = $result->failure;
        $title    = $this->e("{$number}) {$result->displayClass} › {$result->testName}");
        $message  = $this->e($failure?->message ?? '');
        $location = '';

        if ($failure !== null) {
            $cwd      = rtrim((string) getcwd(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            $relFile  = str_replace(['\\', $cwd], ['/', ''], $failure->file);
            $location = $this->e("at {$relFile}:{$failure->line}");
        }

        $snippet = ($failure !== null && $failure->codeLines !== [])
            ? $this->buildCodeSnippet($failure->codeLines, $failure->line)
            : '';

        return <<<HTML
        <div class="failure-card">
            <div class="failure-title">{$title}</div>
            <div class="failure-message">{$message}</div>
            <div class="failure-location">{$location}</div>
            {$snippet}
        </div>
        HTML;
    }

    /**
     * @param array<int, string> $lines  line_number => content
     */
    private function buildCodeSnippet(array $lines, int $errorLine): string
    {
        $html = '<div class="code-snippet">';

        foreach ($lines as $lineNumber => $content) {
            $isError  = $lineNumber === $errorLine;
            $rowClass = $isError ? 'code-line error-line' : 'code-line';
            $arrow    = $isError ? '<span class="code-arrow-err">➜</span>' : '<span class="code-arrow-ok"> </span>';
            $num      = $this->e(str_pad((string) $lineNumber, 4, ' ', STR_PAD_LEFT));

            $expanded = str_replace("\t", '    ', $content);
            $trimmed  = ltrim($expanded);
            $indent   = str_repeat('&nbsp;', strlen($expanded) - strlen($trimmed));
            $code     = $indent.$this->e(rtrim($trimmed));

            $html .= <<<HTML
            <div class="{$rowClass}">
                {$arrow}
                <span class="code-lineno">{$num}</span>
                <span class="code-sep">│</span>
                <span class="code-content">{$code}</span>
            </div>
            HTML;
        }

        $html .= '</div>';

        return $html;
    }

    // -------------------------------------------------------------------------

    private function writeFile(string $path, string $html): void
    {
        $isAbsolute = str_starts_with($path, '/') || (($path[1] ?? '') === ':');
        $fullPath   = $isAbsolute ? $path : getcwd().'/'.$path;
        $dir        = dirname($fullPath);

        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->abort("Could not create directory: {$dir}");
        }

        if (file_put_contents($fullPath, $html) === false) {
            $this->abort("Could not write HTML report to: {$fullPath}");
        }

        fwrite(STDERR, sprintf("\n [pest-plugin-console] HTML report written to: %s\n", $fullPath));
    }

    // -------------------------------------------------------------------------

    private function e(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function abort(string $message): never
    {
        fwrite(STDERR, sprintf("\n \033[1;31m[pest-plugin-console]\033[0m %s\n\n", $message));
        exit(1);
    }
}
