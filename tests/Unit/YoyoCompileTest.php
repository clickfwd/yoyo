<?php

use Clickfwd\Yoyo\YoyoCompiler;
use function Tests\compile_html;
use function Tests\compile_html_with_vars;
use function Tests\encode_vals;
use function Tests\hxattr;
use function Tests\yoattr;
use function Tests\yoprefix_value;

uses()->group('compiler');

test('compiled html uses hardcoded id attribute', function () {
    expect(compile_html('test', '<div id="test"></div>'))->toContain('id="test"');
});

test('compiled html includes Yoyo id in component root encoded vals', function () {
    expect(compile_html('test', '<div id="test"></div>'))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'test'])));
});

test('compiled html `name` attribute matches component name', function () {
    expect(compile_html('foo', '<div></div>'))
        ->toContain(yoattr('name', 'foo'));
});

test('compiled html root includes all attributes', function () {
    $name = 'test';

    expect(compile_html($name, '<div></div>'))
        ->toContain(YoyoCompiler::YOYO_PREFIX.'=""')
        ->toContain(hxattr('get', YoyoCompiler::COMPONENT_DEFAULT_ACTION))
        ->toContain('class="'.YoyoCompiler::COMPONENT_WRAPPER_CLASS.'"')
        ->toContain(hxattr('trigger', 'refresh'))
        ->toContain(hxattr('ext', YoyoCompiler::YOYO_PREFIX))
        ->toContain(hxattr('target', 'this'))
        ->toContain(hxattr('vals'))
        ->toContain(yoattr('name', $name))
        ->toMatch('/id="'.YoyoCompiler::YOYO_PREFIX.'-[a-z0-9]+/i');
});

test('compiled html is wrapped in a new div element multiple child nodes found', function () {
    expect(compile_html('foo', '<div></div><div></div>'))
        ->toMatch('/<div .*><div><\/div><div><\/div><\/div>/');
});

test('compiled html root node excluded on update with innerHTML swap', function () {
    $html = compile_html('foo', '<div '.yoattr('swap', 'innerHTML').'><div>Inner Text</div></div>', $spinning = true);

    expect($html)->toEqual('<div>Inner Text</div>');
});

test('compiled html root node included on update with outerHTML swap', function () {
    $html = compile_html('foo', '<div '.yoattr('swap', 'outerHTML').'><div>Inner Text</div></div>', $spinning = true);

    expect($html)->toMatch('/<div.*'.hxattr('swap', 'outerHTML').'.*><div>Inner Text<\/div><\/div>/');
});

test('compiled html excludes elements with `yoyo:ignore` attribute', function () {
    expect(compile_html('foo', '<div '.yoattr('ignore').'>Foo</div>'))
        ->toEqual('<div>Foo</div>');
});

test('compiled html excludes elements with `yoyo:ignore` attribute on component refresh', function () {
    expect(compile_html('foo', '<div '.yoattr('ignore').'>Foo</div>', $spinning = true))
        ->toEqual('<div>Foo</div>');
});

test('compiled html includes additional extensions', function () {
    expect(compile_html('foo', '<div '.yoattr('ext', 'new-ext').'></div>'))
        ->toMatch('/'.hxattr('ext', 'yoyo, new-ext').'/');
});

test('compiled html detects file inputs and adds the `encoding` attribute to the root form', function () {
    expect(compile_html('foo', '<form><input type="file"/></form>'))
        ->toMatch('/'.hxattr('encoding', 'multipart\/form-data').'/');
});

test('compiled html detects file inputs and adds the `encoding` attribute to child forms', function () {
    expect(compile_html('foo', '<div><form><input type="file"/></form></div>'))
        ->toMatch('/'.hxattr('encoding', 'multipart\/form-data').'/');
});

test('compiled html adds trigger method and `id` to reactive element with yoyo tag', function () {
    expect(compile_html('foo', '<div><button yoyo>foo</button></div>'))
        ->toMatch('/\<button hx-get="render" id="yoyo-(.*)-1">foo\<\/button\>/');
});

test('compiled html preserves reactive element `id` if already present', function () {
    expect(compile_html('foo', '<div><button yoyo id="a">foo</button></div>'))
        ->toMatch('/\<button id="a" hx-get="render">foo\<\/button\>/');
});

test('compiled html correctly parses and merges yoyo:vals attribute in root node', function () {
    expect(compile_html('foo', '<div id="foo" yoyo:vals=\'{"foo":"bar"}\'></div>'))
    ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'foo' => 'bar'])));
});

test('compiled html correctly parses and merges single yoyo:val attribute in root node', function () {
    expect(compile_html('foo', '<div id="foo" yoyo:val.count="1"></div>'))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'count' => 1])));
});

test('compiled html correctly converts kebab-case val key to camel-case', function () {
    expect(compile_html('foo', '<div id="foo" yoyo:val.filter-foo="bar"></div>'))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'filterFoo' => 'bar'])));
});

test('compiled html correctly parses and merges single yoyo:val attribute in child node', function () {
    expect(compile_html('foo', '<div><button yoyo:val.count="1"></button></div>'))
        ->toContain(hxattr('vals', encode_vals(['count' => 1])));
});

test('compiled html correctly parses and merges single yoyo:val attribute in root and child nodes', function () {
    expect(compile_html('foo', '<div id="parent" yoyo:val.foo="1"><button yoyo:val.bar="1"></button></div>'))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'parent', 'foo' => 1])))
        ->toContain(hxattr('vals', encode_vals(['bar' => 1])));
});

test('compiled html correctly parses yoyo:val zero value as integer', function () {
    expect(compile_html('foo', '<div id="foo" yoyo:val.foo="0"></div>'))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'foo' => 0])));
});

test('compiled html includes declared props as vals', function () {
    expect(compile_html_with_vars('foo', '<div id="foo" yoyo:props="foo"></div>', ['foo' => 'bar']))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'foo' => 'bar'])));
});
