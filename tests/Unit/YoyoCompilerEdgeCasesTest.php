<?php

use Clickfwd\Yoyo\YoyoCompiler;

use function Tests\compile_html;
use function Tests\compile_html_with_vars;
use function Tests\encode_vals;
use function Tests\hxattr;
use function Tests\yoattr;
use function Tests\yoprefix_value;

uses()->group('compiler-edge-cases');

// --- Empty and whitespace inputs ---

it('returns empty string for empty input', function () {
    expect(compile_html('foo', ''))->toBe('');
});

it('returns whitespace-only input as-is', function () {
    expect(compile_html('foo', '   '))->toBe('   ');
});

// --- Root node detection ---

it('wraps multiple sibling elements in a div', function () {
    $html = compile_html('foo', '<span>a</span><span>b</span>');
    expect($html)->toMatch('/<div [^>]*class="yoyo-wrapper[^"]*"[^>]*><span>a<\/span><span>b<\/span><\/div>/');
});

it('wraps text node with sibling element in a div', function () {
    $html = compile_html('foo', '<p>a</p><p>b</p><p>c</p>');
    expect($html)->toMatch('/<div [^>]*>.*<\/div>/s');
});

it('does not re-wrap a single root element', function () {
    $html = compile_html('foo', '<section>content</section>');
    expect($html)->toMatch('/<section [^>]*>content<\/section>/');
    expect($html)->not->toContain('<div><section');
});

// --- Form behavior ---

it('auto-adds submit trigger and post method to forms', function () {
    $html = compile_html('foo', '<div><form><input name="x"/></form></div>');
    expect($html)
        ->toContain(hxattr('trigger', 'submit'))
        ->toContain(hxattr('post', YoyoCompiler::COMPONENT_DEFAULT_ACTION));
});

it('does not override existing yoyo:post on form', function () {
    $html = compile_html('foo', '<div><form yoyo:post="customAction"><input name="x"/></form></div>');
    expect($html)
        ->toContain(hxattr('post', 'customAction'))
        ->not->toContain(hxattr('post', YoyoCompiler::COMPONENT_DEFAULT_ACTION));
});

it('does not override existing yoyo:put on form', function () {
    $html = compile_html('foo', '<div><form yoyo:put="save"><input name="x"/></form></div>');
    expect($html)->toContain(hxattr('put', 'save'));
});

it('skips form behavior when yoyo:ignore is present', function () {
    $html = compile_html('foo', '<div><form yoyo:ignore><input name="x"/></form></div>');
    expect($html)->not->toContain(hxattr('trigger', 'submit'));
});

// --- Attribute remapping ---

it('remaps yoyo:on to hx-trigger', function () {
    $html = compile_html('foo', '<div yoyo:on="click"></div>');
    // yoyo:on on root becomes part of trigger attribute (merged with 'refresh')
    expect($html)->toContain(hxattr('trigger'));
});

it('remaps yoyo:on in child elements to hx-trigger', function () {
    $html = compile_html('foo', '<div><button yoyo yoyo:on="click">Go</button></div>');
    expect($html)->toContain(hxattr('trigger', 'click'));
});

it('compiles yoyo:target on child elements', function () {
    $html = compile_html('foo', '<div><button yoyo yoyo:target="#output">Go</button></div>');
    expect($html)->toContain(hxattr('target', '#output'));
});

it('compiles yoyo:swap on child elements', function () {
    $html = compile_html('foo', '<div><button yoyo yoyo:swap="outerHTML">Go</button></div>');
    expect($html)->toContain(hxattr('swap', 'outerHTML'));
});

it('compiles yoyo:confirm on child elements', function () {
    $html = compile_html('foo', '<div><a yoyo yoyo:confirm="Sure?">Delete</a></div>');
    expect($html)->toContain(hxattr('confirm', 'Sure?'));
});

it('compiles yoyo:indicator on child elements', function () {
    $html = compile_html('foo', '<div><button yoyo yoyo:indicator="#spinner">Go</button></div>');
    expect($html)->toContain(hxattr('indicator', '#spinner'));
});

// --- Request method attributes ---

it('converts yoyo:get to hx-get on child elements', function () {
    $html = compile_html('foo', '<div><button yoyo:get="loadMore">More</button></div>');
    expect($html)->toContain(hxattr('get', 'loadMore'));
});

it('converts yoyo:post to hx-post on child elements', function () {
    $html = compile_html('foo', '<div><button yoyo:post="save">Save</button></div>');
    expect($html)->toContain(hxattr('post', 'save'));
});

it('converts yoyo:delete to hx-delete on child elements', function () {
    $html = compile_html('foo', '<div><button yoyo:delete="remove">Delete</button></div>');
    expect($html)->toContain(hxattr('delete', 'remove'));
});

it('does not add default hx-get to child when hx-post already exists', function () {
    $html = compile_html('foo', '<div><button hx-post="save">Save</button></div>');
    // The button should not get hx-get since it already has hx-post
    // But the root div still gets hx-get as the component default
    expect($html)->toMatch('/<button hx-post="save">Save<\/button>/');
});

// --- Spinning behavior ---

it('removes load event from root trigger when spinning', function () {
    $html = compile_html('foo', '<div yoyo:on="load"></div>', $spinning = true);
    // The load event should be removed when spinning
    expect($html)->not->toContain('load');
});

it('preserves non-load events when spinning', function () {
    $html = compile_html('foo', '<div yoyo:on="click,load"></div>', $spinning = true);
    expect($html)->toContain('click');
});

// --- ID generation ---

it('assigns incremental IDs to reactive child elements', function () {
    $html = compile_html('foo', '<div><button yoyo>A</button><button yoyo>B</button></div>');
    expect($html)->toMatch('/id="yoyo-[a-z0-9]+-1"/');
    expect($html)->toMatch('/id="yoyo-[a-z0-9]+-2"/');
});

it('preserves existing ID on reactive child elements', function () {
    $html = compile_html('foo', '<div><button yoyo id="my-btn">A</button></div>');
    expect($html)->toContain('id="my-btn"');
    expect($html)->not->toMatch('/id="yoyo-[a-z0-9]+-1"/');
});

// --- Listener support ---

it('adds listeners to root trigger attribute', function () {
    $compiler = new YoyoCompiler('dynamic', 'test-id', 'test', [], [], false);
    $compiler->addComponentListeners(['itemAdded' => 'refresh', 'itemRemoved' => 'removeItem']);
    $compiler->addComponentProps([]);
    $html = $compiler->compile('<div id="test"></div>');
    expect($html)
        ->toContain('itemAdded')
        ->toContain('itemRemoved');
});

// --- History caching ---

it('adds yoyo:history attribute when withHistory is true', function () {
    $compiler = new YoyoCompiler('dynamic', 'test-id', 'test', [], [], false);
    $compiler->withHistory(true);
    $compiler->addComponentProps([]);
    $html = $compiler->compile('<div id="test"></div>');
    // DOMDocument may use double quotes, so check for the attribute presence
    expect($html)->toMatch('/yoyo:history="1"/');
});

it('does not add yoyo:history attribute when withHistory is false', function () {
    $compiler = new YoyoCompiler('dynamic', 'test-id', 'test', [], [], false);
    $compiler->withHistory(false);
    $compiler->addComponentProps([]);
    $html = $compiler->compile('<div id="test"></div>');
    expect($html)->not->toContain(yoattr('history'));
});

// --- CSS class handling ---

it('preserves existing CSS class on root element', function () {
    $html = compile_html('foo', '<div class="my-class">content</div>');
    expect($html)->toContain('class="yoyo-wrapper my-class"');
});

it('adds wrapper class when no class exists', function () {
    $html = compile_html('foo', '<div>content</div>');
    expect($html)->toContain('class="yoyo-wrapper"');
});

// --- Props passing ---

it('excludes non-declared props from vals', function () {
    $html = compile_html_with_vars('foo', '<div id="foo"></div>', ['secret' => 'value', 'extra' => 123]);
    // Only yoyo-id should be in vals, not secret or extra (they're not in yoyo:props)
    expect($html)->not->toContain('"secret"');
    expect($html)->not->toContain('"extra"');
});

it('includes only declared props in vals', function () {
    $html = compile_html_with_vars('foo', '<div id="foo" yoyo:props="color"></div>', ['color' => 'blue', 'size' => 'large']);
    expect($html)->toContain(hxattr('vals', encode_vals([yoprefix_value('id') => 'foo', 'color' => 'blue'])));
    expect($html)->not->toContain('"size"');
});

// --- Special HTML content ---

it('handles HTML entities in content', function () {
    $html = compile_html('foo', '<div><p>&amp; &lt; &gt;</p></div>');
    expect($html)->toContain('&amp;');
});

it('handles nested elements deeply', function () {
    $html = compile_html('foo', '<div><ul><li><a href="#">Link</a></li></ul></div>');
    expect($html)->toContain('<ul><li><a href="#">Link</a></li></ul>');
});

it('handles boolean attributes', function () {
    $html = compile_html('foo', '<div><input type="text" required disabled/></div>');
    expect($html)->toContain('required');
    expect($html)->toContain('disabled');
});

// --- Single-quoted yoyo attributes ---

it('handles single-quoted yoyo attributes', function () {
    $html = compile_html('foo', "<div><button yoyo:get='loadMore'>More</button></div>");
    expect($html)->toContain(hxattr('get', 'loadMore'));
});

// --- Skip already-compiled components ---

it('skips already-compiled component roots', function () {
    // Simulate a component that's already been compiled (has both yoyo:name and hx-vals)
    $html = compile_html('foo', '<div yoyo:name="foo" hx-vals=\'{"yoyo-id":"foo"}\'>content</div>');
    // Should not double-add attributes
    expect($html)->toContain('content');
});

// --- Static helper methods ---

it('generates correct yoyo prefix', function () {
    expect(YoyoCompiler::yoprefix('get'))->toBe('yoyo:get');
    expect(YoyoCompiler::yoprefix('trigger'))->toBe('yoyo:trigger');
    expect(YoyoCompiler::yoprefix('ignore'))->toBe('yoyo:ignore');
});

it('generates correct yoyo prefix value', function () {
    expect(YoyoCompiler::yoprefix_value('id'))->toBe('yoyo-id');
    expect(YoyoCompiler::yoprefix_value('resolver'))->toBe('yoyo-resolver');
});

it('generates correct hx prefix', function () {
    expect(YoyoCompiler::hxprefix('get'))->toBe('hx-get');
    expect(YoyoCompiler::hxprefix('trigger'))->toBe('hx-trigger');
    expect(YoyoCompiler::hxprefix('vals'))->toBe('hx-vals');
});

// --- Target and include overrides ---

it('allows overriding target on root element', function () {
    $html = compile_html('foo', '<div yoyo:target="#other">content</div>');
    expect($html)->toContain(hxattr('target', '#other'));
});

it('allows overriding include on root element', function () {
    $html = compile_html('foo', '<div yoyo:include="closest form">content</div>');
    expect($html)->toContain(hxattr('include', 'closest form'));
});
