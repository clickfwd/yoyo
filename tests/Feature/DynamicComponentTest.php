<?php

use Clickfwd\Yoyo\Exceptions\BypassRenderMethod;
use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use Clickfwd\Yoyo\Exceptions\HttpException;
use Clickfwd\Yoyo\Yoyo;
use function Tests\encode_vals;
use function Tests\htmlformat;
use function Tests\hxattr;
use function Tests\mockYoyoGetRequest;
use function Tests\render;
use function Tests\resetYoyoRequest;
use function Tests\response;
use function Tests\update;
use function Tests\yoprefix_value;
use function Tests\yoyo_update;
use function Tests\yoyo_view;

uses()->group('unit-dynamic');

beforeAll(function () {
    yoyo_view();
});

it('throws expection on component class not found', function () {
    render('random');
})->throws(ComponentNotFound::class);

it('renders counter component', function () {
    $vars = encode_vals([
        yoprefix_value('id') => 'counter',
        'count' => 0,
    ]);

    expect(render('counter'))->toContain(hxattr('vals', $vars));
});

it('uses passed variable value set in component', function () {
    $vars = encode_vals([yoprefix_value('id') => 'counter', 'count' => 3]);

    expect(render('counter', ['count' => 3]))->toContain(hxattr('vals', $vars));
});

it('updates component', function () {
    expect(update('counter', 'increment'))->toContain('The count is now 1');
});

it('uses passed variable value in component action', function () {
    $vars = encode_vals([yoprefix_value('id') => 'counter', 'count' => 1]);

    expect(update('counter', 'increment'))->toContain(hxattr('vals', $vars));
});

it('throws exception when component method not found', function () {
    update('counter', 'random');
})->throws(ComponentMethodNotFound::class);

it('uses a computed property', function () {
    $output = render('computed-property');
    expect(htmlformat($output))->toEqual(response('computed-property'));
});

it('uses the computed property cache', function () {
    $output = render('computed-property-cache');
    expect(htmlformat($output))->toEqual(response('computed-property-cache'));
});

it('can set component view data', function () {
    expect(render('set-view-data'))->toContain('bar-baz');
});


it('passes action parameters to component method arguments', function () {
    mockYoyoGetRequest('http://example.com/', 'action-arguments/someAction', '', [
        'actionArgs' => [1,'foo'],
    ]);

    $output = yoyo_update();

    resetYoyoRequest();

    expect(htmlformat($output))->toEqual(response('action-arguments'));
});

it('loads dynamic component with registered alias', function () {
    Yoyo::registerComponent('registeredalias', \Tests\App\Yoyo\Registered::class);
    expect(render('registeredalias'))->toContain('id="registered"');
});

it('returns empty response with 204 status on skipRender', function () {
    expect(render('empty-response'))->toBeEmpty()->and(http_response_code())->toBe(204);
})->throws(BypassRenderMethod::class);

it('returns empty response with 200 status on skipRenderAndReplace', function () {
    expect(render('empty-response-and-remove'))->toBeEmpty()->and(http_response_code())->toBe(200);
});

it('dynamically resolves class and named arguments in mount method', function () {
    mockYoyoGetRequest('http://example.com/', 'ependency-injection-class-with-named-argument-mapping', '', [
        'id' => 100,
    ]);
    
    expect(render('dependency-injection-class-with-named-argument-mapping'))->toContain('the comment title-100');

    resetYoyoRequest();
});

it('executes trait lifecycle hooks', function () {
    expect(render('component-with-trait'))->toContain('{ComponentWithTrait} saw that {mountWithFramework} was here');
});

it('it aborts component execution and throws an exception', function () {
    try {
        render('abort');
    } catch (HttpException $e) {
        expect($e->getHeaders())->toMatchArray(['foo' => 'bar'])
            ->and($e->getStatusCode())->toBe(404)
            ->and($e->getMessage())->toBe('not found');

        throw $e;
    }
})->throws(HttpException::class);

it('renders dynamic component in sub-directory', function () {
    expect(render('account.register'))->toContain('Please register to access this page');
});
