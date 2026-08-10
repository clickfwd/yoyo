<?php

use Tests\App\Comment;

use function Tests\mockYoyoGetRequest;
use function Tests\render;
use function Tests\resetYoyoRequest;
use function Tests\yoyo_view;

/**
 * Lifecycle hooks receive one array built from caller-supplied variables merged with
 * incoming request data, and the container matches it by parameter NAME before it
 * consults the type. A request variable that happens to share a container slot's name
 * therefore displaces the object the component asked for.
 *
 * Request values arrive JSON-decoded, so they reach that slot as real PHP types --
 * int, float, bool, array, null -- not only as strings.
 */
beforeEach(function () {
    yoyo_view();
});

afterEach(function () {
    resetYoyoRequest();
});

// --- A request variable must never displace a container slot ---

dataset('decoded request values', [
    'int'          => ['32'],
    'float'        => ['1.5'],
    'bool'         => ['false'],
    'array'        => ['{}'],
    'string'       => [''],
    'null literal' => ['null'],
]);

it('keeps the container object when a request variable shares the mount slot name', function ($value) {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection', '', ['post' => $value]);

    expect(render('lifecycle-injection'))->toContain('post=the comment title');
})->with('decoded request values');

it('keeps the container object when a request variable shares the initialize slot name', function ($value) {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection', '', ['comment' => $value]);

    expect(render('lifecycle-injection'))->toContain('comment=the comment title');
})->with('decoded request values');

it('keeps the container object for an inherited lifecycle hook', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection-inherited', '', [
        'post'    => '32',
        'comment' => '32',
    ]);

    expect(render('lifecycle-injection-inherited'))
        ->toContain('comment=the comment title')
        ->toContain('post=the comment title');
});

it('keeps the container object for a trait lifecycle hook', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection-with-trait', '', ['comment' => '32']);

    expect(render('lifecycle-injection-with-trait'))->toContain('comment=the comment title');
});

it('keeps the container object when a request variable is the literal null', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection-nullable', '', ['post' => 'null']);

    expect(render('lifecycle-injection-nullable'))
        ->toContain('post=the comment title')
        ->not->toContain('NULL-INJECTED');
});

// --- Controls: these pass before the change and must keep passing after it ---

it('still fills a caller-supplied slot from the request', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection', '', ['id' => 77]);

    expect(render('lifecycle-injection'))->toContain('id=77');
});

it('still applies the default when the request omits a caller-supplied slot', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection', '', []);

    expect(render('lifecycle-injection'))->toContain('id=0');
});

it('still lets a caller-supplied variable fill a container slot', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection', '', []);

    $sentinel = new class extends Comment
    {
        public function title()
        {
            return 'caller supplied';
        }
    };

    expect(render('lifecycle-injection', ['comment' => $sentinel]))->toContain('comment=caller supplied');
});

it('still fills both a container slot and a caller-supplied slot together', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection', '', ['id' => 5]);

    expect(render('lifecycle-injection'))
        ->toContain('comment=the comment title')
        ->toContain('post=the comment title')
        ->toContain('id=5');
});
