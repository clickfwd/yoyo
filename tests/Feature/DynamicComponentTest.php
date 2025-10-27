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
use function Tests\mockYoyoPostRequest;
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

it('renders component using dynamic properties', function () {
    $vars = encode_vals([
        yoprefix_value('id') => 'counter',
        'count' => '',
    ]);

    expect(render('counter_dynamic_properties'))->toContain(hxattr('vals', $vars));
});

it('updates component with dynamic properties', function () {
    expect(update('counter_dynamic_properties', 'increment'))->toContain('The count is now 1');
});

// Variadic Parameters Tests
it('handles variadic parameters with no arguments', function () {
    mockYoyoPostRequest('/', 'variadic-parameters/onlyVariadic', 'variadic-parameters', [
        'actionArgs' => [],
    ]);

    expect(yoyo_update())->toContain('Received: []');
});

it('handles variadic parameters with multiple arguments', function () {
    mockYoyoPostRequest('/', 'variadic-parameters/onlyVariadic', 'variadic-parameters', [
        'actionArgs' => ['arg1', 'arg2', 'arg3'],
    ]);

    expect(yoyo_update())->toContain('Received: ["arg1","arg2","arg3"]');
});

it('handles mixed regular and variadic parameters', function () {
    mockYoyoPostRequest('/', 'variadic-parameters/mixedVariadic', 'variadic-parameters', [
        'actionArgs' => ['first', 'second', 'third'],
    ]);

    expect(yoyo_update())->toContain('First: first, Rest: ["second","third"]');
});

it('handles optional and variadic parameters', function () {
    mockYoyoPostRequest('/', 'variadic-parameters/optionalAndVariadic', 'variadic-parameters', [
        'actionArgs' => ['required_value', 'optional_value', 'extra1', 'extra2'],
    ]);

    expect(yoyo_update())->toContain('Required: required_value, Optional: optional_value, Extra: ["extra1","extra2"]');
});

// Dependency Injection Tests
it('handles action with only typed parameters', function () {
    mockYoyoPostRequest('/', 'dependency-injection-action/onlyTyped', 'dependency-injection-action', [
        'actionArgs' => [],
    ]);

    expect(yoyo_update())->toContain('Post title: the comment title');
});

it('handles action with multiple typed parameters', function () {
    mockYoyoPostRequest('/', 'dependency-injection-action/multipleTyped', 'dependency-injection-action', [
        'actionArgs' => [],
    ]);

    expect(yoyo_update())->toContain('Post: the comment title, Comment: the comment body');
});

it('handles action with mixed typed and regular parameters', function () {
    mockYoyoPostRequest('/', 'dependency-injection-action/mixedTypedAndRegular', 'dependency-injection-action', [
        'actionArgs' => [123, 'inactive'],
    ]);

    expect(yoyo_update())->toContain('Post: the comment title, ID: 123, Status: inactive');
});

it('handles action with typed and variadic parameters', function () {
    mockYoyoPostRequest('/', 'dependency-injection-action/typedWithVariadic', 'dependency-injection-action', [
        'actionArgs' => ['php', 'laravel', 'yoyo'],
    ]);

    expect(yoyo_update())->toContain('Post: the comment title, Tags: ["php","laravel","yoyo"]');
});

it('handles action with typed and optional regular parameter without value', function () {
    mockYoyoPostRequest('/', 'dependency-injection-action/typedWithOptional', 'dependency-injection-action', [
        'actionArgs' => [],
    ]);

    expect(yoyo_update())->toContain('Post: the comment title, Status: default');
});

it('handles action with typed and optional regular parameter with value', function () {
    mockYoyoPostRequest('/', 'dependency-injection-action/typedWithOptional', 'dependency-injection-action', [
        'actionArgs' => ['active'],
    ]);

    expect(yoyo_update())->toContain('Post: the comment title, Status: active');
});
