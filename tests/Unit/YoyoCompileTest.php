<?php

use Clickfwd\Yoyo\YoyoCompiler;
use function Tests\compile_html;
use function Tests\compile_html_with_vars;
use function Tests\encode_vals;
use function Tests\hxattr;
use function Tests\yoattr;
use function Tests\yoprefix_value;

uses()->group('compiler');

it('uses hardcoded id attribute', function () {
    expect(compile_html('test', '<div id="test"></div>'))->toContain('id="test"');
});

it('adds yoyo:id to component root', function () {
    expect(compile_html('test', '<div id="test"></div>'))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'test'])));
});

it('includes component `name` attribute', function () {
    expect(compile_html('foo', '<div></div>'))
        ->toContain(yoattr('name', 'foo'));
});

it('includes all attributes', function () {
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

it('wraps output in a new div element when multiple child nodes found', function () {
    expect(compile_html('foo', '<div></div><div></div>'))
        ->toMatch('/<div .*><div><\/div><div><\/div><\/div>/');
});

it('excludes root node on innerHTML swap', function () {
    $html = compile_html('foo', '<div '.yoattr('swap', 'innerHTML').'><div>Inner Text</div></div>', $spinning = true);

    expect($html)->toEqual('<div>Inner Text</div>');
});

it('includes root node on outerHTML swap', function () {
    $html = compile_html('foo', '<div '.yoattr('swap', 'outerHTML').'><div>Inner Text</div></div>', $spinning = true);

    expect($html)->toMatch('/<div.*'.hxattr('swap', 'outerHTML').'.*><div>Inner Text<\/div><\/div>/');
});

it('skips elements with `yoyo:ignore` attribute', function () {
    expect(compile_html('foo', '<div '.yoattr('ignore').'>Foo</div>'))
        ->toEqual('<div>Foo</div>');
});

it('skips elements with `yoyo:ignore` attribute on update', function () {
    expect(compile_html('foo', '<div '.yoattr('ignore').'>Foo</div>', $spinning = true))
        ->toEqual('<div>Foo</div>');
});

it('includes additional extensions', function () {
    expect(compile_html('foo', '<div '.yoattr('ext', 'new-ext').'></div>'))
        ->toMatch('/'.hxattr('ext', 'yoyo, new-ext').'/');
});

it('detects file inputs and adds the `encoding` attribute to the root form', function () {
    expect(compile_html('foo', '<form><input type="file"/></form>'))
        ->toMatch('/'.hxattr('encoding', 'multipart\/form-data').'/');
});

it('detects file inputs and adds the `encoding` attribute to child forms', function () {
    expect(compile_html('foo', '<div><form><input type="file"/></form></div>'))
        ->toMatch('/'.hxattr('encoding', 'multipart\/form-data').'/');
});

it('adds trigger method and `id` to elements with yoyo tag', function () {
    expect(compile_html('foo', '<div><button yoyo>foo</button></div>'))
        ->toMatch('/\<button hx-get="render" id="yoyo-(.*)-1">foo\<\/button\>/');
});

it('preserves reactive element `id` if already present', function () {
    expect(compile_html('foo', '<div><button yoyo id="a">foo</button></div>'))
        ->toMatch('/\<button id="a" hx-get="render">foo\<\/button\>/');
});

it('parses and merges yoyo:vals attribute in root node', function () {
    expect(compile_html('foo', '<div id="foo" yoyo:vals=\'{"foo":"bar"}\'></div>'))
    ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'foo' => 'bar'])));
});

it('parses and merges single yoyo:val attribute in root node', function () {
    expect(compile_html('foo', '<div id="foo" yoyo:val.count="1"></div>'))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'count' => 1])));
});

it('converts kebab-case val key to camel-case', function () {
    expect(compile_html('foo', '<div id="foo" yoyo:val.filter-foo="bar"></div>'))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'filterFoo' => 'bar'])));
});

it('parses and merges single yoyo:val attribute in child node', function () {
    expect(compile_html('foo', '<div><button yoyo:val.count="1"></button></div>'))
        ->toContain(hxattr('vals', encode_vals(['count' => 1])));
});

it('parses and merges single yoyo:val attribute in root and child nodes', function () {
    expect(compile_html('foo', '<div id="parent" yoyo:val.foo="1"><button yoyo:val.bar="1"></button></div>'))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'parent', 'foo' => 1])))
        ->toContain(hxattr('vals', encode_vals(['bar' => 1])));
});

it('parses yoyo:val zero value as integer', function () {
    expect(compile_html('foo', '<div id="foo" yoyo:val.foo="0"></div>'))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'foo' => 0])));
});

it('includes declared props as vals', function () {
    expect(compile_html_with_vars('foo', '<div id="foo" yoyo:props="foo"></div>', ['foo' => 'bar']))
        ->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'foo' => 'bar'])));
});

it('correctly compiles component with non-ascii characters', function () {
    expect(compile_html('foo', '<div><p>áéíóü</p></div>'))
        ->toContain('áéíóü');
});

it('correctly compiles component with Chinese characters', function () {
    expect(compile_html('foo', '<div><p>极简、极速、极致、 海豚PHP、PHP开发框架、后台框架</p></div>'))
        ->toContain('极简、极速、极致、 海豚PHP、PHP开发框架、后台框架');
});
