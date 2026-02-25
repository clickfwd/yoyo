<?php

use Clickfwd\Yoyo\ClassHelpers;
use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\ComponentResolver;
use Clickfwd\Yoyo\QueryString;
use Clickfwd\Yoyo\Request;
use Clickfwd\Yoyo\Yoyo;
use Clickfwd\Yoyo\YoyoHelpers;
use Tests\App\Yoyo\Counter;

// --- Operator precedence fix in ClassHelpers::getPublicProperties ---

it('applies correct operator precedence with baseClass filter', function () {
    ClassHelpers::flushCache();

    $resolver = new ComponentResolver(Yoyo::getInstance());
    $component = new Counter($resolver, 'test-counter', 'counter');

    // With baseClass: should exclude base class properties but include subclass properties
    $props = ClassHelpers::getPublicProperties($component, Component::class);

    expect($props)->toContain('count');
    expect($props)->not->toContain('redirectTo'); // From Redirector trait on base class
});

it('returns all public properties when no baseClass is provided', function () {
    ClassHelpers::flushCache();

    $resolver = new ComponentResolver(Yoyo::getInstance());
    $component = new Counter($resolver, 'test-counter', 'counter');

    // Without baseClass: should include properties from all classes including Component
    $props = ClassHelpers::getPublicProperties($component, null);

    // Should include at least the subclass property
    expect($props)->toContain('count');
});

// --- fullUrl() using $this->server instead of raw $_SERVER ---

it('uses mocked server data in fullUrl() instead of raw $_SERVER', function () {
    $request = new Request();

    $request->mock([], [
        'HTTP_HOST' => 'mocked.example.com',
        'REQUEST_URI' => '/mocked-path',
        'HTTPS' => 'on',
    ]);

    $url = $request->fullUrl();

    expect($url)->toBe('https://mocked.example.com/mocked-path');
});

it('returns HX_CURRENT_URL when available from mocked server', function () {
    $request = new Request();

    $request->mock([], [
        'HTTP_HX_CURRENT_URL' => 'https://htmx.example.com/page',
        'HTTP_HOST' => 'other.com',
    ]);

    expect($request->fullUrl())->toBe('https://htmx.example.com/page');
});

it('returns null when no host is available', function () {
    $request = new Request();

    $request->mock([], []);

    expect($request->fullUrl())->toBeNull();
});

it('constructs http URL when HTTPS is not set', function () {
    $request = new Request();

    $request->mock([], [
        'HTTP_HOST' => 'example.com',
        'REQUEST_URI' => '/path?query',
    ]);

    expect($request->fullUrl())->toBe('http://example.com/path?query');
});

// --- stripslashes removal: test_json no longer corrupts backslashes ---

it('does not strip legitimate backslashes from JSON values', function () {
    // A JSON string containing a backslash in a value (e.g., a Windows path)
    $json = '{"path":"C:\\\\Users\\\\test"}';
    $decoded = YoyoHelpers::test_json($json);

    expect($decoded)->toBe(['path' => 'C:\\Users\\test']);
});

it('still decodes valid JSON without backslashes', function () {
    $json = '{"key":"value","number":42}';
    $decoded = YoyoHelpers::test_json($json);

    expect($decoded)->toBe(['key' => 'value', 'number' => 42]);
});

it('returns null for non-JSON strings', function () {
    expect(YoyoHelpers::test_json('just a string'))->toBeNull();
    expect(YoyoHelpers::test_json(''))->toBeNull();
});

it('returns array input as-is from test_json', function () {
    $input = ['already' => 'decoded'];
    expect(YoyoHelpers::test_json($input))->toBe($input);
});

it('returns null for non-string non-array input', function () {
    expect(YoyoHelpers::test_json(42))->toBeNull();
    expect(YoyoHelpers::test_json(null))->toBeNull();
    expect(YoyoHelpers::test_json(true))->toBeNull();
});

// --- randString uses random_int (not predictable rand) ---

it('generates string of correct length', function () {
    expect(strlen(YoyoHelpers::randString(8)))->toBe(8);
    expect(strlen(YoyoHelpers::randString(16)))->toBe(16);
    expect(strlen(YoyoHelpers::randString(1)))->toBe(1);
});

it('generates only alphanumeric lowercase characters', function () {
    $result = YoyoHelpers::randString(100);
    expect($result)->toMatch('/^[0-9a-z]+$/');
});

it('generates different strings on consecutive calls', function () {
    $results = [];
    for ($i = 0; $i < 20; $i++) {
        $results[] = YoyoHelpers::randString(16);
    }

    // All should be unique (extremely high probability with 16-char strings)
    expect(count(array_unique($results)))->toBe(20);
});

// --- Configuration URL escaping ---

it('uses json_encode for URL in JavaScript init code', function () {
    $initCode = \Clickfwd\Yoyo\Services\Configuration::javascriptInitCode(false);

    // The URL value should be json_encode'd (double-quoted), not single-quoted interpolation
    // json_encode('') produces '""', so output should be: Yoyo.url = "";
    expect($initCode)->toMatch('/Yoyo\.url = ".*";/');
    expect($initCode)->not->toMatch("/Yoyo\\.url = '.*';/");
});

// --- QueryString operator precedence fix ---

it('removes query params matching defaults with correct precedence', function () {
    $_SERVER = ['HTTP_HX_CURRENT_URL' => 'http://example.com/?count=5'];

    $request = new Request();
    Yoyo::getInstance()->bindRequest($request);
    $request->mock([], ['HTTP_HX_CURRENT_URL' => 'http://example.com/?count=5']);

    $qs = new QueryString(
        ['count' => 0],      // defaults
        ['count' => 0],      // new (matches default)
        ['count']             // keys
    );

    $pageParams = $qs->getPageQueryParams();

    // count=0 matches the default, so it should be removed
    expect($pageParams)->not->toHaveKey('count');
});

it('keeps query params that differ from defaults', function () {
    $request = new Request();
    Yoyo::getInstance()->bindRequest($request);
    $request->mock([], ['HTTP_HX_CURRENT_URL' => 'http://example.com/']);

    $qs = new QueryString(
        ['count' => 0],
        ['count' => 5],
        ['count']
    );

    $pageParams = $qs->getPageQueryParams();

    expect($pageParams)->toHaveKey('count');
    expect($pageParams['count'])->toBe(5);
});
