<?php

use Clickfwd\Yoyo\YoyoCompiler;

use function Tests\compile_html;
use function Tests\yoyo_instance;

beforeAll(function () {
    Tests\yoyo_view();
});

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
    $perOp = $elapsed / $iterations;

    fwrite(STDERR, sprintf(
        "  %-55s %6d ops  %8.3fms total  %8.4fms/op\n",
        $label,
        $iterations,
        $elapsed,
        $perOp,
    ));

    return ['label' => $label, 'iterations' => $iterations, 'total_ms' => $elapsed, 'per_op_ms' => $perOp];
}

// --- Phase breakdown: isolate individual steps of compile() ---

test('BENCH: preg_replace yoyo prefix finder', function () {
    $html = '<div yoyo:get="action" yoyo:trigger="click" yoyo:target="#foo"><button yoyo:post="save" yoyo:confirm="Sure?">Save</button></div>';
    $prefix = YoyoCompiler::YOYO_PREFIX;
    $finder = YoyoCompiler::YOYO_PREFIX_FINDER;

    $result = bench('preg_replace (prefix finder)', 5000, function () use ($html, $prefix, $finder) {
        preg_replace('/ '.$prefix.':(.*)="(.*)"/U', " $finder $prefix:\$1=\"\$2\"", $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: mb_encode_numericentity (ASCII only)', function () {
    $html = '<div><p>Hello World</p><button>Click me</button></div>';

    $result = bench('mb_encode_numericentity (ASCII)', 5000, function () use ($html) {
        mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8');
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: mb_encode_numericentity (unicode)', function () {
    $html = '<div><p>极简、极速、极致 海豚PHP áéíóü café naïve</p></div>';

    $result = bench('mb_encode_numericentity (unicode)', 5000, function () use ($html) {
        mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8');
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: DOMDocument loadHTML', function () {
    $html = '<html><body><div><p>Hello</p><button>Click</button></div></body></html>';

    $result = bench('DOMDocument::loadHTML', 5000, function () use ($html) {
        $dom = new DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors($internalErrors);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: DOMDocument loadHTML + saveHTML', function () {
    $html = '<html><body><div><p>Hello</p><button>Click</button></div></body></html>';

    $result = bench('DOMDocument load+save', 5000, function () use ($html) {
        $dom = new DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors($internalErrors);
        foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $node) {
            $dom->saveHTML($node);
        }
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: DOMXPath query', function () {
    $dom = new DOMDocument();
    $dom->loadHTML('<html><body><div><form><input type="file"/></form><button yoyo-finder yoyo="">Test</button></div></body></html>');
    $xpath = new DOMXPath($dom);

    $result = bench('DOMXPath::query', 5000, function () use ($xpath) {
        $xpath->query('//form');
        $xpath->query('//*[@yoyo]|//*[@yoyo-finder]');
        $xpath->query('/html/body/*');
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

// --- Full compile at different complexity levels ---

test('BENCH: compile() minimal HTML', function () {
    $html = '<div>Hello</div>';
    $result = bench('compile(minimal)', 2000, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: compile() with 5 yoyo children', function () {
    $html = '<div>';
    for ($i = 0; $i < 5; $i++) {
        $html .= '<button yoyo:get="action'.$i.'" yoyo:target="#out" yoyo:confirm="Sure?">Btn '.$i.'</button>';
    }
    $html .= '</div>';

    $result = bench('compile(5 yoyo children)', 1000, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: compile() with 20 yoyo children', function () {
    $html = '<div>';
    for ($i = 0; $i < 20; $i++) {
        $html .= '<button yoyo:get="action'.$i.'" yoyo:target="#out" yoyo:confirm="Sure?">Btn '.$i.'</button>';
    }
    $html .= '</div>';

    $result = bench('compile(20 yoyo children)', 500, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: compile() with form + file inputs', function () {
    $html = '<div><form><input type="text" name="title"/><input type="file" name="doc"/><textarea name="body"></textarea><button type="submit">Save</button></form></div>';

    $result = bench('compile(form+file)', 1000, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: compile() with yoyo:vals JSON', function () {
    $html = '<div id="foo" yoyo:vals=\'{"count":0,"filter":"active","page":1,"sort":"name"}\'><p>Content</p></div>';

    $result = bench('compile(yoyo:vals JSON)', 1000, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: compile() with multiple yoyo:val attributes', function () {
    $html = '<div id="foo" yoyo:val.count="0" yoyo:val.filter="active" yoyo:val.page="1" yoyo:val.sort-field="name"><p>Content</p></div>';

    $result = bench('compile(4x yoyo:val attrs)', 1000, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: compile() realistic component (todo-list sized)', function () {
    $html = '<div id="todo-list">';
    $html .= '<form yoyo:post="add"><input type="text" name="task" placeholder="Add task"/></form>';
    $html .= '<ul>';
    for ($i = 0; $i < 10; $i++) {
        $html .= '<li>';
        $html .= '<input type="checkbox" yoyo:post="toggle" yoyo:val.id="'.$i.'"/>';
        $html .= '<span>Task '.$i.'</span>';
        $html .= '<button yoyo:delete="remove" yoyo:val.id="'.$i.'" yoyo:confirm="Delete?">×</button>';
        $html .= '</li>';
    }
    $html .= '</ul>';
    $html .= '<div yoyo:get="filter" yoyo:target="#todo-list" yoyo:trigger="click">All | Active | Done</div>';
    $html .= '</div>';

    $result = bench('compile(realistic todo-list)', 500, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: compile() large table (50 rows)', function () {
    $html = '<div id="data-table"><table><thead><tr><th>ID</th><th>Name</th><th>Action</th></tr></thead><tbody>';
    for ($i = 0; $i < 50; $i++) {
        $html .= '<tr><td>'.$i.'</td><td>Item '.$i.'</td>';
        $html .= '<td><button yoyo:post="edit" yoyo:val.id="'.$i.'">Edit</button>';
        $html .= '<button yoyo:delete="remove" yoyo:val.id="'.$i.'" yoyo:confirm="Sure?">Delete</button></td></tr>';
    }
    $html .= '</tbody></table></div>';

    $result = bench('compile(50-row table)', 200, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: compile() innerHTML swap (spinning)', function () {
    $html = '<div yoyo:swap="innerHTML"><p>Content 1</p><p>Content 2</p><p>Content 3</p></div>';

    $result = bench('compile(innerHTML swap, spinning)', 1000, function () use ($html) {
        compile_html('test', $html, $spinning = true);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

// --- Summary ---

test('BENCH: compile cost breakdown summary', function () {
    fwrite(STDERR, "\n  --- Compile Cost Breakdown ---\n");

    // Minimal
    $html = '<div>Hello</div>';
    $simple = bench('  SUMMARY: minimal div', 2000, function () use ($html) {
        compile_html('test', $html);
    });

    // With prefix regex
    $html = '<div yoyo:get="action"><button yoyo:post="save">Save</button></div>';
    $attrs = bench('  SUMMARY: with yoyo: attrs', 2000, function () use ($html) {
        compile_html('test', $html);
    });

    // With vals
    $html = '<div id="x" yoyo:val.a="1" yoyo:val.b="2" yoyo:val.c="3"><p>Content</p></div>';
    $vals = bench('  SUMMARY: with yoyo:val attrs', 2000, function () use ($html) {
        compile_html('test', $html);
    });

    fwrite(STDERR, sprintf(
        "\n  Cost of yoyo attrs: +%.4fms/op (%.0f%% overhead)\n",
        $attrs['per_op_ms'] - $simple['per_op_ms'],
        (($attrs['per_op_ms'] / $simple['per_op_ms']) - 1) * 100
    ));
    fwrite(STDERR, sprintf(
        "  Cost of val parsing: +%.4fms/op (%.0f%% overhead vs minimal)\n\n",
        $vals['per_op_ms'] - $simple['per_op_ms'],
        (($vals['per_op_ms'] / $simple['per_op_ms']) - 1) * 100
    ));

    expect($simple['per_op_ms'])->toBeLessThan(1.0);
})->group('benchmark');
