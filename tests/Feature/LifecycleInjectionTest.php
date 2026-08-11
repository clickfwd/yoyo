<?php

use Clickfwd\Yoyo\Yoyo;
use Tests\App\Comment;
use Tests\App\SiteRequest;
use Tests\App\SiteRequestContract;

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

    expect(render('lifecycle-injection'))->toContain('post=POST-OBJECT');
})->with('decoded request values');

it('keeps the container object when a request variable shares the initialize slot name', function ($value) {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection', '', ['comment' => $value]);

    expect(render('lifecycle-injection'))->toContain('comment=COMMENT-OBJECT');
})->with('decoded request values');

it('keeps the container object for an inherited lifecycle hook', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection-inherited', '', [
        'post'    => '32',
        'comment' => '32',
    ]);

    expect(render('lifecycle-injection-inherited'))
        ->toContain('comment=COMMENT-OBJECT')
        ->toContain('post=POST-OBJECT');
});

it('keeps the container object for a trait lifecycle hook', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection-with-trait', '', ['comment' => '32']);

    expect(render('lifecycle-injection-with-trait'))->toContain('comment=COMMENT-OBJECT');
});

it('keeps the container object when a request variable is the literal null', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection-nullable', '', ['post' => 'null']);

    expect(render('lifecycle-injection-nullable'))
        ->toContain('post=POST-OBJECT')
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

    $sentinel = new class () extends Comment {
        public function label(): string
        {
            return 'CALLER-SUPPLIED';
        }
    };

    expect(render('lifecycle-injection', ['comment' => $sentinel]))->toContain('comment=CALLER-SUPPLIED');
});

it('still fills both a container slot and a caller-supplied slot together', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection', '', ['id' => 5]);

    expect(render('lifecycle-injection'))
        ->toContain('comment=COMMENT-OBJECT')
        ->toContain('post=POST-OBJECT')
        ->toContain('id=5');
});

// --- The widest real signature: several container slots at once, one an interface,
//     one named "request" (the most common slot name in practice) ---

beforeEach(function () {
    Yoyo::container()->set(SiteRequestContract::class, SiteRequest::class);
});

it('keeps every container object when all of their names arrive as request variables', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection-production-shape', '', [
        'request'      => '32',
        'pageSettings' => 'null',
        'category'     => '{}',
        'listing'      => 'false',
    ]);

    expect(render('lifecycle-injection-production-shape'))
        ->toContain('request=REQUEST-OBJECT')
        ->toContain('pageSettings=PAGE-SETTINGS')
        ->toContain('category=CATEGORY-MODEL')
        ->toContain('listing=LISTING-MODEL');
});

it('keeps the container object for an interface-typed slot', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection-production-shape', '', ['request' => '32']);

    expect(render('lifecycle-injection-production-shape'))->toContain('request=REQUEST-OBJECT');
});

it('still fills a caller-supplied slot alongside container slots', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection-production-shape', '', [
        'request' => '32',
        'slug'    => 'from-request',
    ]);

    expect(render('lifecycle-injection-production-shape'))
        ->toContain('request=REQUEST-OBJECT')
        ->toContain('slug=from-request');
});

// --- Precedence for caller-supplied slots ---
//
// Container slots take the container's object and request data is withheld from them.
// For a caller-supplied slot there is no container involved, and both sources may
// carry the same key. Request data wins, which is the precedence that predates this
// change; locking it down keeps the merge from being reordered by accident.

it('lets request data win over a caller variable for a caller-supplied slot', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection', '', ['id' => 77]);

    expect(render('lifecycle-injection', ['id' => 41]))->toContain('id=77');
});

it('uses the caller variable for a caller-supplied slot the request does not carry', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection', '', []);

    expect(render('lifecycle-injection', ['id' => 41]))->toContain('id=41');
});

// --- A hook with no container slots keeps every request value ---

it('passes every request value to a hook that has only caller-supplied slots', function () {
    mockYoyoGetRequest('http://example.com/', 'lifecycle-injection-caller-only', '', [
        'alpha' => 'from-request-alpha',
        'beta'  => 'from-request-beta',
    ]);

    expect(render('lifecycle-injection-caller-only'))
        ->toContain('alpha=from-request-alpha')
        ->toContain('beta=from-request-beta');
});
