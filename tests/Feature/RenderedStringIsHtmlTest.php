<?php

/**
 * A string returned from render() is finished HTML. Compiling it again as a template turned
 * template syntax inside user input into code, so only an explicit inline() view compiles.
 */

use function Tests\render;
use function Tests\update;
use function Tests\yoyo_blade;

beforeAll(function () {
    yoyo_blade();
});

it('prints template syntax from user input literally on first render', function () {
    $output = render('renders-finished-html', ['title' => '{{ 7*7 }} @php echo 13*3; @endphp']);

    expect($output)->toContain('>{{ 7*7 }} @php echo 13*3; @endphp</div>')
        ->and($output)->not->toContain('>49 39</div>');
});

it('prints template syntax from user input literally on update', function () {
    $output = update('renders-finished-html', 'render', ['title' => '{{ 7*7 }}']);

    expect($output)->toContain('{{ 7*7 }}')
        ->and($output)->not->toContain('>49</div>');
});

it('compiles a view the component explicitly marks as inline', function () {
    expect(render('inline-template'))->toContain('>Hello inline</div>');
});
