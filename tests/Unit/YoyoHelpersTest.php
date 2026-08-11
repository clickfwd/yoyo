<?php

use Clickfwd\Yoyo\YoyoHelpers;

// --- encode_vals ---

it('encodes simple key-value pairs to JSON', function () {
    $result = YoyoHelpers::encode_vals(['name' => 'test', 'count' => 5]);
    $decoded = json_decode($result, true);

    expect($decoded)->toBe(['name' => 'test', 'count' => 5]);
});

it('appends [] suffix for array values in encode_vals', function () {
    $result = YoyoHelpers::encode_vals(['tags' => ['php', 'yoyo']]);
    $decoded = json_decode($result, true);

    expect($decoded)->toHaveKey('tags[]');
    expect($decoded['tags[]'])->toBe(['php', 'yoyo']);
});

it('encodes unicode characters without escaping', function () {
    $result = YoyoHelpers::encode_vals(['name' => '日本語']);

    expect($result)->toContain('日本語');
});

it('escapes HTML-sensitive characters in encode_vals', function () {
    $result = YoyoHelpers::encode_vals(['html' => '<script>alert("xss")</script>']);

    // Should escape quotes, tags, ampersands
    expect($result)->not->toContain('<script>');
    expect($result)->not->toContain('"xss"');
});

it('handles empty array in encode_vals', function () {
    expect(YoyoHelpers::encode_vals([]))->toBe('[]');
});

// --- decode_vals ---

it('decodes valid JSON string to array', function () {
    $result = YoyoHelpers::decode_vals('{"key":"value","num":42}');
    expect($result)->toBe(['key' => 'value', 'num' => 42]);
});

it('returns empty array for empty string in decode_vals', function () {
    expect(YoyoHelpers::decode_vals(''))->toBe([]);
});

// --- decode_val ---

it('decodes JSON value via decode_val', function () {
    expect(YoyoHelpers::decode_val('{"key":"value"}'))->toBe(['key' => 'value']);
});

it('converts string "0" to integer 0 in decode_val', function () {
    expect(YoyoHelpers::decode_val('0'))->toBe(0);
});

it('returns non-JSON string as-is from decode_val', function () {
    expect(YoyoHelpers::decode_val('hello'))->toBe('hello');
});

it('decodes JSON scalar null to PHP null in decode_val', function () {
    expect(YoyoHelpers::decode_val('null'))->toBeNull();
});

it('decodes JSON scalar false to PHP false in decode_val', function () {
    expect(YoyoHelpers::decode_val('false'))->toBeFalse();
});

it('decodes JSON scalar true to PHP true in decode_val', function () {
    expect(YoyoHelpers::decode_val('true'))->toBeTrue();
});

// --- test_json ---

it('flags JSON scalar null as valid in test_json', function () {
    $valid = false;
    $decoded = YoyoHelpers::test_json('null', $valid);

    expect($valid)->toBeTrue();
    expect($decoded)->toBeNull();
});

it('flags JSON scalar false as valid in test_json', function () {
    $valid = false;
    $decoded = YoyoHelpers::test_json('false', $valid);

    expect($valid)->toBeTrue();
    expect($decoded)->toBeFalse();
});

it('flags JSON object as valid in test_json', function () {
    $valid = false;
    $decoded = YoyoHelpers::test_json('{"a":1}', $valid);

    expect($valid)->toBeTrue();
    expect($decoded)->toBe(['a' => 1]);
});

it('marks invalid JSON string as not valid in test_json', function () {
    $valid = false;
    $decoded = YoyoHelpers::test_json('hello', $valid);

    expect($valid)->toBeFalse();
    expect($decoded)->toBeNull();
});

it('treats array input as already-decoded valid JSON in test_json', function () {
    $valid = false;
    $decoded = YoyoHelpers::test_json(['a' => 1], $valid);

    expect($valid)->toBeTrue();
    expect($decoded)->toBe(['a' => 1]);
});

it('marks non-string non-array input as not valid in test_json', function () {
    $valid = false;
    $decoded = YoyoHelpers::test_json(123, $valid);

    expect($valid)->toBeFalse();
    expect($decoded)->toBeNull();
});

// --- studly ---

it('converts kebab-case to StudlyCase', function () {
    expect(YoyoHelpers::studly('foo-bar'))->toBe('FooBar');
    expect(YoyoHelpers::studly('foo-bar-baz'))->toBe('FooBarBaz');
});

it('converts snake_case to StudlyCase', function () {
    expect(YoyoHelpers::studly('foo_bar'))->toBe('FooBar');
});

it('handles already StudlyCase input', function () {
    expect(YoyoHelpers::studly('FooBar'))->toBe('FooBar');
});

it('handles single word in studly', function () {
    expect(YoyoHelpers::studly('foo'))->toBe('Foo');
});

it('supports custom delimiter in studly', function () {
    expect(YoyoHelpers::studly('foo.bar', '.'))->toBe('FooBar');
});

// --- camel ---

it('converts kebab-case to camelCase', function () {
    expect(YoyoHelpers::camel('foo-bar'))->toBe('fooBar');
    expect(YoyoHelpers::camel('foo-bar-baz'))->toBe('fooBarBaz');
});

it('converts snake_case to camelCase', function () {
    expect(YoyoHelpers::camel('foo_bar'))->toBe('fooBar');
});

it('handles single word in camel', function () {
    expect(YoyoHelpers::camel('foo'))->toBe('foo');
});

it('supports custom delimiter in camel', function () {
    expect(YoyoHelpers::camel('foo.bar', '.'))->toBe('fooBar');
});

// --- snake ---

it('converts camelCase to snake_case', function () {
    expect(YoyoHelpers::snake('fooBar'))->toBe('foo_bar');
    expect(YoyoHelpers::snake('fooBarBaz'))->toBe('foo_bar_baz');
});

it('converts StudlyCase to snake_case', function () {
    expect(YoyoHelpers::snake('FooBar'))->toBe('foo_bar');
});

it('returns already lowercase string unchanged', function () {
    expect(YoyoHelpers::snake('foobar'))->toBe('foobar');
});

it('supports custom delimiter in snake', function () {
    expect(YoyoHelpers::snake('fooBar', '-'))->toBe('foo-bar');
});

// --- removeEmptyValues ---

it('removes null values', function () {
    $array = ['a' => 'value', 'b' => null, 'c' => 'other'];
    YoyoHelpers::removeEmptyValues($array);

    expect($array)->toBe(['a' => 'value', 'c' => 'other']);
});

it('removes empty string values', function () {
    $array = ['a' => 'value', 'b' => '', 'c' => 'other'];
    YoyoHelpers::removeEmptyValues($array);

    expect($array)->toBe(['a' => 'value', 'c' => 'other']);
});

it('removes empty nested arrays', function () {
    $array = ['a' => 'value', 'b' => ['nested' => null]];
    YoyoHelpers::removeEmptyValues($array);

    expect($array)->toBe(['a' => 'value']);
});

it('preserves zero values', function () {
    $array = ['a' => 0, 'b' => '0', 'c' => false];
    YoyoHelpers::removeEmptyValues($array);

    expect($array)->toHaveKey('a');
    expect($array)->toHaveKey('b');
});

it('handles already empty array', function () {
    $array = [];
    $result = YoyoHelpers::removeEmptyValues($array);

    expect($result)->toBe([]);
});
