<?php

use Clickfwd\Yoyo\Services\Configuration;

// Tests work against the configuration set in Pest.php (namespace = Tests\App\Yoyo\)

it('returns configured value with get()', function () {
    expect(Configuration::get('namespace'))->toBe('Tests\\App\\Yoyo\\');
});

it('returns default when key not found', function () {
    expect(Configuration::get('nonexistent', 'fallback'))->toBe('fallback');
});

it('returns null when key not found and no default', function () {
    expect(Configuration::get('nonexistent'))->toBeNull();
});

it('provides default config values', function () {
    expect(Configuration::get('defaultSwapStyle'))->toBe('outerHTML');
    expect(Configuration::get('historyEnabled'))->toBeFalse();
    expect(Configuration::get('indicatorClass'))->toBe('yoyo-indicator');
    expect(Configuration::get('requestClass'))->toBe('yoyo-request');
    expect(Configuration::get('settlingClass'))->toBe('yoyo-settling');
    expect(Configuration::get('swappingClass'))->toBe('yoyo-swapping');
});

it('generates htmx source URL with default version', function () {
    $src = Configuration::htmxSrc();

    expect($src)->toContain('htmx.org@');
    expect($src)->toContain('/dist/htmx.min.js');
});

it('generates yoyo source path with default config', function () {
    $src = Configuration::yoyoSrc();

    expect($src)->toContain('yoyo.js');
});

it('generates JavaScript assets with script tags', function () {
    $assets = Configuration::javascriptAssets();

    expect($assets)->toContain('<script src=');
    expect($assets)->toContain('htmx');
    expect($assets)->toContain('yoyo.js');
});

it('generates JavaScript init code with script tag by default', function () {
    $code = Configuration::javascriptInitCode();

    expect($code)->toContain('<script>');
    expect($code)->toContain('Yoyo.url');
    expect($code)->toContain('Yoyo.config(');
});

it('generates JavaScript init code without script tag', function () {
    $code = Configuration::javascriptInitCode(false);

    expect($code)->not->toContain('<script>');
    expect($code)->toContain('Yoyo.url');
});

it('excludes non-allowed config options from JavaScript config', function () {
    $code = Configuration::javascriptInitCode(false);

    // namespace and url are not in allowedConfigOptions
    expect($code)->not->toContain('Tests\\\\App\\\\Yoyo');
    // Allowed options should be present
    expect($code)->toContain('outerHTML'); // defaultSwapStyle
    expect($code)->toContain('yoyo-indicator'); // indicatorClass
});

it('generates CSS styles with style tag', function () {
    $styles = Configuration::cssStyle();

    expect($styles)->toContain('<style>');
    expect($styles)->toContain('yoyo\\:spinning');
    expect($styles)->toContain('display: none');
});

it('generates CSS styles without style tag', function () {
    $styles = Configuration::cssStyle(false);

    expect($styles)->not->toContain('<style>');
    expect($styles)->toContain('display: none');
});

it('minifies scripts output', function () {
    $scripts = Configuration::scripts();

    // Should not contain tabs or multiple spaces
    expect($scripts)->not->toMatch('/\t/');
    expect($scripts)->not->toMatch('/\s{2,}/');
});

it('minifies styles output', function () {
    $styles = Configuration::styles();

    expect($styles)->not->toMatch('/\t/');
});

it('uses json_encode for URL value in JavaScript init code', function () {
    $code = Configuration::javascriptInitCode(false);

    // URL should be double-quoted via json_encode, not single-quoted
    expect($code)->toMatch('/Yoyo\.url = ".*";/');
    expect($code)->not->toMatch("/Yoyo\\.url = '.*';/");
});
