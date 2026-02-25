<?php

use Clickfwd\Yoyo\ClassHelpers;
use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\ComponentResolver;
use Clickfwd\Yoyo\Request;
use Clickfwd\Yoyo\Yoyo;
use Tests\App\Yoyo\ComponentWithTrait;
use Tests\App\Yoyo\Counter;

use function Tests\compile_html;
use function Tests\mockYoyoGetRequest;
use function Tests\render;
use function Tests\resetYoyoRequest;
use function Tests\update;
use function Tests\yoyo_view;

beforeAll(function () {
    yoyo_view();
});

function benchmark(string $label, int $iterations, Closure $fn): array
{
    // Warmup
    for ($i = 0; $i < 10; $i++) {
        $fn();
    }

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }
    $elapsed = (hrtime(true) - $start) / 1e6; // ms

    $perOp = $elapsed / $iterations;

    fwrite(STDERR, sprintf(
        "BENCH [%s] %d iterations: %.3fms total, %.4fms/op\n",
        $label,
        $iterations,
        $elapsed,
        $perOp
    ));

    return ['label' => $label, 'iterations' => $iterations, 'total_ms' => $elapsed, 'per_op_ms' => $perOp];
}

function createComponentInstance(string $class = Counter::class, string $name = 'counter'): Component
{
    $resolver = new ComponentResolver(Yoyo::getInstance());

    return new $class($resolver, 'bench-'.$name, $name);
}

// --- ClassHelpers Reflection ---

test('BENCH: getPublicProperties', function () {
    $component = createComponentInstance();

    $result = benchmark('ClassHelpers::getPublicProperties', 5000, function () use ($component) {
        ClassHelpers::getPublicProperties($component, Component::class);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: getDefaultPublicVars', function () {
    $component = createComponentInstance();

    $result = benchmark('ClassHelpers::getDefaultPublicVars', 5000, function () use ($component) {
        ClassHelpers::getDefaultPublicVars($component, Component::class);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: classUsesRecursive', function () {
    $result = benchmark('ClassHelpers::classUsesRecursive', 5000, function () {
        ClassHelpers::classUsesRecursive(ComponentWithTrait::class);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: getPublicMethods', function () {
    $result = benchmark('ClassHelpers::getPublicMethods', 5000, function () {
        ClassHelpers::getPublicMethods(Counter::class, ['render']);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

// --- Request JSON decoding ---

test('BENCH: Request::all() with JSON values', function () {
    $_REQUEST = [
        'name' => 'test',
        'count' => '5',
        'data' => '{"key":"value","nested":{"a":1}}',
        'list' => '[1,2,3]',
        'plain' => 'hello',
        'yoyo-id' => 'yoyo-abc123',
    ];
    $request = new Request();

    $result = benchmark('Request::all()', 5000, function () use ($request) {
        $request->all();
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: Request::get() repeated access', function () {
    $_REQUEST = ['data' => '{"key":"value"}'];
    $request = new Request();

    $result = benchmark('Request::get() x3', 5000, function () use ($request) {
        $request->get('data');
        $request->get('data');
        $request->get('data');
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

// --- Full render pipeline ---

test('BENCH: full component render (Counter)', function () {
    $result = benchmark('render(Counter)', 500, function () {
        render('counter', ['count' => 0]);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: full component update (Counter::increment)', function () {
    $result = benchmark('update(Counter::increment)', 500, function () {
        mockYoyoGetRequest('http://localhost/', 'counter/increment');
        update('counter', 'increment', ['count' => 0]);
        resetYoyoRequest();
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

// --- YoyoCompiler ---

test('BENCH: YoyoCompiler::compile() simple HTML', function () {
    $html = '<div><p>Hello World</p></div>';
    $result = benchmark('YoyoCompiler::compile(simple)', 1000, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: YoyoCompiler::compile() with yoyo attributes', function () {
    $html = '<div><button yoyo:post="save">Save</button><form><input type="text" name="title"></form></div>';
    $result = benchmark('YoyoCompiler::compile(yoyo+form)', 1000, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

test('BENCH: YoyoCompiler::compile() with unicode', function () {
    $html = '<div><p>日本語テスト Unicode: äöü ñ</p></div>';
    $result = benchmark('YoyoCompiler::compile(unicode)', 1000, function () use ($html) {
        compile_html('test', $html);
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');

// --- Computed properties ---

test('BENCH: computed property access', function () {
    $result = benchmark('render(computed-property)', 500, function () {
        render('computed-property');
    });
    expect($result['total_ms'])->toBeGreaterThan(0);
})->group('benchmark');
