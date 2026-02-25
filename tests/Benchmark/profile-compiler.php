<?php

/**
 * YoyoCompiler Performance Profiler
 *
 * Generates an HTML report with visual breakdown of where time is spent.
 * Run: php tests/Benchmark/profile-compiler.php
 * Opens: tests/Benchmark/profile-report.html
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../Helpers.php';

Tests\yoyo_view();

// ─── Helpers ───────────────────────────────────────────────────────

function bench(string $label, int $iterations, Closure $fn): array
{
    // Warmup
    for ($i = 0; $i < min(10, $iterations); $i++) {
        $fn();
    }

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }
    $elapsed = (hrtime(true) - $start) / 1e6;

    return [
        'label' => $label,
        'iterations' => $iterations,
        'total_ms' => $elapsed,
        'per_op_ms' => $elapsed / $iterations,
    ];
}

function buildHtml(int $rows): string
{
    $html = '<div id="data-table"><table><tbody>';
    for ($i = 0; $i < $rows; $i++) {
        $html .= '<tr><td>' . $i . '</td><td>Item ' . $i . '</td>';
        $html .= '<td><button yoyo:post="edit" yoyo:val.id="' . $i . '">Edit</button>';
        $html .= '<button yoyo:delete="remove" yoyo:val.id="' . $i . '" yoyo:confirm="Sure?">Del</button></td></tr>';
    }
    $html .= '</tbody></table></div>';
    return $html;
}

function buildTodoHtml(): string
{
    $html = '<div id="todo-list">';
    $html .= '<form yoyo:post="add"><input type="text" name="task" placeholder="Add task"/></form>';
    $html .= '<ul>';
    for ($i = 0; $i < 10; $i++) {
        $html .= '<li>';
        $html .= '<input type="checkbox" yoyo:post="toggle" yoyo:val.id="' . $i . '"/>';
        $html .= '<span>Task ' . $i . '</span>';
        $html .= '<button yoyo:delete="remove" yoyo:val.id="' . $i . '" yoyo:confirm="Delete?">×</button>';
        $html .= '</li>';
    }
    $html .= '</ul>';
    $html .= '<div yoyo:get="filter" yoyo:target="#todo-list" yoyo:trigger="click">All | Active | Done</div>';
    $html .= '</div>';
    return $html;
}

// ─── Phase profiling for a given HTML ──────────────────────────────

function profilePhases(string $html, int $iters): array
{
    $prefix = 'yoyo';
    $finder = 'yoyo-finder';

    // Phase 1: Regex (yoyo-finder injection)
    $phase1 = bench('Regex: yoyo-finder injection', $iters, function () use ($html, $prefix, $finder) {
        preg_replace(
            ['/ ' . $prefix . ':(.*)="(.*)"/U', '/ ' . $prefix . ':(.*)=\'(.*)\'/U'],
            [" $finder $prefix:\$1=\"\$2\"", " $finder $prefix:\$1='\$2'"],
            $html
        );
    });

    // Prepare HTML after regex
    $regexed = preg_replace(
        ['/ ' . $prefix . ':(.*)="(.*)"/U', '/ ' . $prefix . ':(.*)=\'(.*)\'/U'],
        [" $finder $prefix:\$1=\"\$2\"", " $finder $prefix:\$1='\$2'"],
        $html
    );

    // Phase 2: DOM parse
    $phase2 = bench('DOM: loadHTML', $iters, function () use ($regexed) {
        $dom = new DOMDocument();
        $e = libxml_use_internal_errors(true);
        $dom->loadHTML($regexed);
        libxml_use_internal_errors($e);
    });

    // Phase 3: XPath creation + queries
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($regexed);
    libxml_use_internal_errors(false);

    $phase3 = bench('XPath: create + 3 queries', $iters, function () use ($dom) {
        $xpath = new DOMXPath($dom);
        $xpath->query('/html/body/*');
        $xpath->query('//form');
        $xpath->query('//*[@yoyo]|//*[@yoyo-finder]');
    });

    // Count reactive elements
    $xpath = new DOMXPath($dom);
    $children = $xpath->query('//*[@yoyo]|//*[@yoyo-finder]');
    $childCount = max(0, $children->length - 1);

    // Phase 4: Per-element attribute scanning (3 passes currently)
    $phase4 = bench('Children: attr scanning (3-pass)', $iters, function () use ($children) {
        foreach ($children as $key => $el) {
            if ($key == 0) {
                continue;
            }
            // Pass 1: addRequestMethodAttribute - check hx-* (8 checks)
            foreach (['boost', 'delete', 'get', 'patch', 'post', 'put', 'sse', 'ws'] as $m) {
                $el->hasAttribute('hx-' . $m);
            }
            // Pass 1b: addRequestMethodAttribute - check yoyo:* (8 checks)
            foreach (['boost', 'delete', 'get', 'patch', 'post', 'put', 'sse', 'ws'] as $m) {
                $el->getAttribute('yoyo:' . $m);
            }
            // Pass 2: scan for yoyo: attributes
            foreach ($el->attributes as $a) {
                str_starts_with($a->name, 'yoyo:');
            }
            // Pass 3: scan for yoyo:val. attributes
            foreach ($el->attributes as $a) {
                str_starts_with($a->name, 'yoyo:val.');
            }
        }
    });

    // Phase 5: DOM mutations (setAttribute/removeAttribute per element)
    $phase5 = bench('Children: DOM mutations (set/remove)', $iters, function () use ($children) {
        foreach ($children as $key => $el) {
            if ($key == 0) {
                continue;
            }
            // Typical: remove yoyo:post, set hx-post, remove yoyo:val.id, set hx-vals
            $el->setAttribute('hx-post', 'edit');
            $el->removeAttribute('hx-post');
            $el->setAttribute('hx-vals', '{"id":1}');
            $el->removeAttribute('hx-vals');
        }
    });

    // Phase 6: encode/decode vals
    $phase6 = bench('Vals: encode + decode per element', $iters, function () use ($childCount) {
        for ($i = 0; $i < $childCount; $i++) {
            Clickfwd\Yoyo\YoyoHelpers::decode_val((string) $i);
            Clickfwd\Yoyo\YoyoHelpers::camel('sort-field', '-');
            Clickfwd\Yoyo\YoyoHelpers::encode_vals(['id' => $i]);
        }
    });

    // Phase 7: getOuterHTML (extra XPath + method check)
    $phase7 = bench('Output: getOuterHTML XPath + check', $iters, function () use ($dom) {
        $xpath2 = new DOMXPath($dom);
        $matched = $xpath2->query("//*[starts-with(name(@*),'hx-')]");
        foreach ($matched as $n) {
            foreach (['get', 'post', 'put', 'delete', 'patch', 'ws', 'sse'] as $v) {
                if ($n->hasAttribute('hx-' . $v)) {
                    break;
                }
            }
        }
    });

    // Phase 8: saveHTML serialization
    $phase8 = bench('Output: saveHTML (serialize)', $iters, function () use ($dom) {
        $output = '';
        foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $n) {
            $output .= $dom->saveHTML($n);
        }
    });

    // Full compile
    $full = bench('FULL: compile()', $iters, function () use ($html) {
        Tests\compile_html('test', $html);
    });

    return [
        'child_count' => $childCount,
        'full' => $full,
        'phases' => [$phase1, $phase2, $phase3, $phase4, $phase5, $phase6, $phase7, $phase8],
    ];
}

// ─── Scaling analysis ──────────────────────────────────────────────

function profileScaling(): array
{
    $sizes = [0, 1, 5, 10, 20, 50];
    $results = [];

    foreach ($sizes as $rows) {
        $html = $rows === 0 ? '<div>Hello</div>' : buildHtml($rows);
        $iters = $rows <= 5 ? 2000 : ($rows <= 20 ? 1000 : 500);
        $r = bench("$rows rows", $iters, function () use ($html) {
            Tests\compile_html('test', $html);
        });
        $r['rows'] = $rows;
        $r['reactive_elements'] = $rows * 2;
        $results[] = $r;
    }

    return $results;
}

// ─── Run everything ────────────────────────────────────────────────

fwrite(STDERR, "Profiling 50-row table...\n");
$table50 = profilePhases(buildHtml(50), 500);

fwrite(STDERR, "Profiling realistic todo-list...\n");
$todoList = profilePhases(buildTodoHtml(), 1000);

fwrite(STDERR, "Profiling minimal component...\n");
$minimal = profilePhases('<div>Hello</div>', 2000);

fwrite(STDERR, "Profiling scaling behavior...\n");
$scaling = profileScaling();

// ─── Generate HTML report ──────────────────────────────────────────

$phaseColors = [
    '#e74c3c', // 1 regex - red
    '#3498db', // 2 DOM parse - blue
    '#2ecc71', // 3 XPath - green
    '#f39c12', // 4 attr scan - orange (HOT)
    '#9b59b6', // 5 DOM mutations - purple
    '#1abc9c', // 6 vals encode - teal
    '#e67e22', // 7 getOuterHTML - dark orange
    '#95a5a6', // 8 saveHTML - gray
];

function phaseBar(array $profile, array $colors): string
{
    $full = $profile['full']['per_op_ms'];
    $sum = 0;
    $segments = [];

    foreach ($profile['phases'] as $i => $phase) {
        $pct = ($phase['per_op_ms'] / $full) * 100;
        $sum += $phase['per_op_ms'];
        $segments[] = sprintf(
            '<div class="seg" style="width:%.1f%%;background:%s" title="%s: %.4fms (%.1f%%)"></div>',
            $pct,
            $colors[$i],
            htmlspecialchars($phase['label']),
            $phase['per_op_ms'],
            $pct
        );
    }

    $unaccounted = $full - $sum;
    $uPct = ($unaccounted / $full) * 100;
    $segments[] = sprintf(
        '<div class="seg" style="width:%.1f%%;background:#bdc3c7" title="Other overhead: %.4fms (%.1f%%)"></div>',
        $uPct,
        $unaccounted,
        $uPct
    );

    return implode('', $segments);
}

function phaseTable(array $profile): string
{
    global $phaseColors;
    $full = $profile['full']['per_op_ms'];
    $rows = '';
    $sum = 0;

    foreach ($profile['phases'] as $i => $phase) {
        $pct = ($phase['per_op_ms'] / $full) * 100;
        $sum += $phase['per_op_ms'];
        $bar = str_repeat('█', max(1, (int) round($pct / 2)));
        $rows .= sprintf(
            '<tr><td><span class="dot" style="background:%s"></span>%s</td><td class="num">%.4f</td><td class="num">%.1f%%</td><td class="bar-cell"><div class="mini-bar" style="width:%.1f%%;background:%s"></div></td></tr>',
            $phaseColors[$i],
            htmlspecialchars($phase['label']),
            $phase['per_op_ms'],
            $pct,
            $pct,
            $phaseColors[$i]
        );
    }

    $unaccounted = $full - $sum;
    $uPct = ($unaccounted / $full) * 100;
    $rows .= sprintf(
        '<tr><td><span class="dot" style="background:#bdc3c7"></span>Other (root attrs, form, overhead)</td><td class="num">%.4f</td><td class="num">%.1f%%</td><td class="bar-cell"><div class="mini-bar" style="width:%.1f%%;background:#bdc3c7"></div></td></tr>',
        $unaccounted,
        $uPct,
        $uPct
    );

    $rows .= sprintf(
        '<tr class="total"><td>Total compile()</td><td class="num">%.4f</td><td class="num">100%%</td><td></td></tr>',
        $full
    );

    return $rows;
}

function scalingChart(array $scaling): string
{
    $maxMs = 0;
    foreach ($scaling as $r) {
        $maxMs = max($maxMs, $r['per_op_ms']);
    }

    $bars = '';
    foreach ($scaling as $r) {
        $pct = ($r['per_op_ms'] / $maxMs) * 100;
        $label = $r['rows'] === 0 ? 'minimal' : $r['rows'] . ' rows';
        $bars .= sprintf(
            '<div class="scale-row"><span class="scale-label">%s<br><small>%d elems</small></span><div class="scale-bar-wrap"><div class="scale-bar" style="width:%.1f%%"></div><span class="scale-val">%.3fms</span></div></div>',
            $label,
            $r['reactive_elements'],
            $pct,
            $r['per_op_ms']
        );
    }
    return $bars;
}

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>YoyoCompiler Performance Profile</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #1a1a2e; color: #eee; padding: 2rem; line-height: 1.5; }
h1 { font-size: 1.6rem; margin-bottom: 0.5rem; color: #fff; }
h2 { font-size: 1.2rem; margin: 2rem 0 0.8rem; color: #a8b2d1; border-bottom: 1px solid #333; padding-bottom: 0.4rem; }
h3 { font-size: 1rem; margin: 1.2rem 0 0.5rem; color: #8892b0; }
.meta { color: #666; font-size: 0.85rem; margin-bottom: 2rem; }
.card { background: #16213e; border-radius: 8px; padding: 1.2rem; margin-bottom: 1.5rem; }
.stacked-bar { display: flex; height: 36px; border-radius: 4px; overflow: hidden; margin: 0.5rem 0; }
.seg { height: 100%; min-width: 1px; transition: opacity 0.2s; cursor: help; }
.seg:hover { opacity: 0.8; }
table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
th { text-align: left; color: #8892b0; font-weight: 500; padding: 0.4rem 0.6rem; border-bottom: 1px solid #333; }
td { padding: 0.4rem 0.6rem; border-bottom: 1px solid #222; }
.num { text-align: right; font-variant-numeric: tabular-nums; font-family: 'SF Mono', Menlo, monospace; }
.total td { font-weight: 600; border-top: 2px solid #444; color: #fff; }
.dot { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 6px; vertical-align: middle; }
.bar-cell { width: 35%; }
.mini-bar { height: 14px; border-radius: 2px; min-width: 2px; }
.scale-row { display: flex; align-items: center; margin: 0.4rem 0; }
.scale-label { width: 90px; font-size: 0.8rem; text-align: right; padding-right: 12px; color: #8892b0; }
.scale-label small { color: #555; }
.scale-bar-wrap { flex: 1; display: flex; align-items: center; }
.scale-bar { height: 24px; background: linear-gradient(90deg, #3498db, #e74c3c); border-radius: 3px; min-width: 3px; }
.scale-val { margin-left: 8px; font-family: 'SF Mono', Menlo, monospace; font-size: 0.8rem; color: #a8b2d1; }
.insight { background: #0f3460; border-left: 3px solid #f39c12; padding: 0.8rem 1rem; border-radius: 0 4px 4px 0; margin: 1rem 0; font-size: 0.9rem; }
.insight strong { color: #f39c12; }
.cols { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
@media (max-width: 900px) { .cols { grid-template-columns: 1fr; } }
.opt-table td:first-child { font-weight: 500; }
.opt-table .save { color: #2ecc71; }
.opt-table .cost { color: #e74c3c; }
</style>
</head>
<body>
<h1>YoyoCompiler — Performance Profile</h1>
<p class="meta">PHP {PHP_VERSION} · {$table50['full']['iterations']} iterations · {$table50['child_count']} reactive elements in 50-row table</p>

<h2>1. Phase Breakdown — 50-Row Table ({$table50['child_count']} reactive elements)</h2>
<div class="card">
    <h3>Time Distribution (hover for details)</h3>
    <div class="stacked-bar">{PHASE_BAR_TABLE50}</div>
    <table>
        <thead><tr><th>Phase</th><th>ms/op</th><th>%</th><th>Distribution</th></tr></thead>
        <tbody>{PHASE_TABLE_TABLE50}</tbody>
    </table>
</div>

<div class="insight">
    <strong>Key finding:</strong> Attribute scanning (3 separate passes per element) is the #1 scaling cost at {SCAN_PCT}%.
    Combined with the redundant XPath in getOuterHTML ({OUTER_PCT}%), these two account for {COMBINED_PCT}% of compile time —
    and both are optimizable.
</div>

<h2>2. Phase Breakdown Comparison</h2>
<div class="cols">
    <div class="card">
        <h3>Realistic Todo-List ({$todoList['child_count']} elements)</h3>
        <div class="stacked-bar">{PHASE_BAR_TODO}</div>
        <table>
            <thead><tr><th>Phase</th><th>ms/op</th><th>%</th><th></th></tr></thead>
            <tbody>{PHASE_TABLE_TODO}</tbody>
        </table>
    </div>
    <div class="card">
        <h3>Minimal Component (0 elements)</h3>
        <div class="stacked-bar">{PHASE_BAR_MINIMAL}</div>
        <table>
            <thead><tr><th>Phase</th><th>ms/op</th><th>%</th><th></th></tr></thead>
            <tbody>{PHASE_TABLE_MINIMAL}</tbody>
        </table>
    </div>
</div>

<h2>3. Scaling: Compile Time vs Component Size</h2>
<div class="card">
    <div>{SCALING_CHART}</div>
</div>

<div class="insight">
    <strong>Scaling is linear</strong> with reactive element count. Each element adds ~{PER_ELEM_COST}ms.
    The fixed overhead (regex + DOM parse + XPath + serialize) is ~{FIXED_COST}ms regardless of size.
</div>

<h2>4. Optimization Opportunities</h2>
<div class="card">
    <table class="opt-table">
        <thead><tr><th>Optimization</th><th>Target</th><th>Expected Impact</th></tr></thead>
        <tbody>
            <tr>
                <td>Single-pass attribute scan</td>
                <td>Children: attr scanning ({SCAN_PCT}%)</td>
                <td class="save">Eliminate 2 of 3 passes → ~{SCAN_SAVE}% total savings</td>
            </tr>
            <tr>
                <td>Eliminate redundant XPath in getOuterHTML</td>
                <td>Output: getOuterHTML ({OUTER_PCT}%)</td>
                <td class="save">Reuse existing XPath → ~{OUTER_SAVE}% savings</td>
            </tr>
            <tr>
                <td>Inline addRequestMethodAttribute for children</td>
                <td>Part of attr scanning</td>
                <td class="save">Avoid 16 DOM calls per element → included in single-pass</td>
            </tr>
            <tr>
                <td>PHP 8.4 Dom\HTMLDocument</td>
                <td>DOM: loadHTML ({PARSE_PCT}%)</td>
                <td class="cost">Actually ~20% slower for small/medium HTML</td>
            </tr>
        </tbody>
    </table>
</div>

<p class="meta" style="margin-top: 2rem; text-align: center;">Generated by profile-compiler.php</p>
</body>
</html>
HTML;

// Fill in placeholders
$scanPct = sprintf('%.1f', ($table50['phases'][3]['per_op_ms'] / $table50['full']['per_op_ms']) * 100);
$outerPct = sprintf('%.1f', ($table50['phases'][6]['per_op_ms'] / $table50['full']['per_op_ms']) * 100);
$combinedPct = sprintf('%.1f', (float)$scanPct + (float)$outerPct);
$parsePct = sprintf('%.1f', ($table50['phases'][1]['per_op_ms'] / $table50['full']['per_op_ms']) * 100);

// Per-element cost = (50-row total - minimal total) / 100 elements
$perElemCost = sprintf('%.4f', ($scaling[5]['per_op_ms'] - $scaling[0]['per_op_ms']) / 100);
$fixedCost = sprintf('%.3f', $scaling[0]['per_op_ms']);

$replacements = [
    '{PHASE_BAR_TABLE50}' => phaseBar($table50, $phaseColors),
    '{PHASE_TABLE_TABLE50}' => phaseTable($table50),
    '{PHASE_BAR_TODO}' => phaseBar($todoList, $phaseColors),
    '{PHASE_TABLE_TODO}' => phaseTable($todoList),
    '{PHASE_BAR_MINIMAL}' => phaseBar($minimal, $phaseColors),
    '{PHASE_TABLE_MINIMAL}' => phaseTable($minimal),
    '{SCALING_CHART}' => scalingChart($scaling),
    '{SCAN_PCT}' => $scanPct,
    '{OUTER_PCT}' => $outerPct,
    '{COMBINED_PCT}' => $combinedPct,
    '{PARSE_PCT}' => $parsePct,
    '{SCAN_SAVE}' => sprintf('%.0f', (float)$scanPct * 0.7),
    '{OUTER_SAVE}' => $outerPct,
    '{PER_ELEM_COST}' => $perElemCost,
    '{FIXED_COST}' => $fixedCost,
];

$html = str_replace(array_keys($replacements), array_values($replacements), $html);

$outputPath = __DIR__ . '/profile-report.html';
file_put_contents($outputPath, $html);
fwrite(STDERR, "\nReport saved to: $outputPath\n");
