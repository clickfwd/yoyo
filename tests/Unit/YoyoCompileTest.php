<?php

use Clickfwd\Yoyo\YoyoCompiler;
use function Tests\compile_html;
use function Tests\encode_vars;
use function Tests\hxattr;
use function Tests\yoattr;
use function Tests\yoprefix_value;

test('component uses hardcoded id attribute', function () {
    $html = compile_html('test', '<div id="test"></div>');

    expect($html)
        ->toContain('id="test"')
        ->toContain(hxattr('vars', encode_vars([yoprefix_value('id') => 'test'])))
        ->toContain(hxattr('include', '#test *'));
});

test('component name attribute matches name', function () {
    expect(compile_html('foo', '<div></div>'))
        ->toContain(yoattr('name', 'foo'));
});

test('compiled root includes all attributes', function () {
    $name = 'test';

    $html = compile_html($name, '<div></div>');

    expect($html)
        ->toContain(YoyoCompiler::YOYO_PREFIX.'=""')
        ->toContain(hxattr('get', YoyoCompiler::COMPONENT_DEFAULT_ACTION))
        ->toContain('class="'.YoyoCompiler::COMPONENT_WRAPPER_CLASS.'"')
        ->toContain(hxattr('trigger', 'refresh'))
        ->toContain(hxattr('ext', YoyoCompiler::YOYO_PREFIX))
        ->toContain(hxattr('target', 'this'))
        ->toContain(hxattr('vars'))
        ->toContain(yoattr('name', $name))
        ->toMatch('/id="'.YoyoCompiler::YOYO_PREFIX.'-[a-z0-9]+/i');
});

test('root node created when there are multiple child nodes', function () {
    $html = compile_html('foo', '<div></div><div></div>');

    expect($html)->toMatch('/<div .*><div><\/div><div><\/div><\/div>/');
});

test('root node excluded on update with innerHTML', function () {
    $html = compile_html('foo', '<div '.yoattr('swap', 'innerHTML').'><div>Inner Text</div></div>', $spinning = true);

    expect($html)->toEqual('<div>Inner Text</div>');
});

test('root node included on update with outerHTML', function () {
    $html = compile_html('foo', '<div '.yoattr('swap', 'outerHTML').'><div>Inner Text</div></div>', $spinning = true);

    expect($html)->toMatch('/<div.*'.hxattr('swap', 'outerHTML').'.*><div>Inner Text<\/div><\/div>/');
});

test('static output is not compiled on render', function () {
    expect(compile_html('foo', '<div '.yoattr('ignore').'>Foo</div>'))
        ->toEqual('<div>Foo</div>');
})->group('static');

test('static output is not compiled on update', function () {
    expect(compile_html('foo', '<div '.yoattr('ignore').'>Foo</div>', $spinning = true))
        ->toEqual('<div>Foo</div>');
})->group('static');
