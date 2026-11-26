<?php

use Clickfwd\Yoyo\Containers\IlluminateContainer;
use Clickfwd\Yoyo\Containers\YoyoContainer;
use Clickfwd\Yoyo\Yoyo;
use Illuminate\Container\Container;

use function Tests\mockYoyoGetRequest;
use function Tests\render;
use function Tests\resetYoyoRequest;
use function Tests\yoyo_update;
use function Tests\yoyo_view;

beforeEach(function () {
    $this->previousContainer = Yoyo::container();
});

afterEach(function () {
    resetYoyoRequest();
    new Yoyo($this->previousContainer);
});

dataset('presence containers', [
    'native' => ['native'],
    'illuminate' => ['illuminate'],
]);

dataset('present action arguments', [
    'omitted nullable default' => ['optional', [], '"fallback"'],
    'explicit null' => ['optional', [null], 'null'],
    'omitted typed default' => ['typedOptional', [], 'fallback'],
    'required nullable' => ['requiredNullable', [null], 'null'],
    'omitted before variadic' => ['optionalVariadic', ['first'], '["first","fallback",[]]'],
    'null before variadic' => ['optionalVariadic', ['first', null, 'last'], '["first",null,["last"]]'],
    'supplied before variadic' => ['optionalVariadic', ['first', 'second', 'last'], '["first","second",["last"]]'],
    'all optional before variadic' => ['onlyOptionalVariadic', [], '["fallback",[]]'],
]);

it('preserves action argument presence', function ($container, $action, $arguments, $expected) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'input-presence/' . $action, '', [
        'actionArgs' => json_encode($arguments),
    ]);

    expect(yoyo_update())->toContain('result=' . $expected . '</span>');
})->with('presence containers')->with('present action arguments');

dataset('present event arguments', [
    'empty positional' => ['optional-event', [], '"fallback"'],
    'positional null' => ['optional-event', [null], 'null'],
    'named null' => ['optional-event', ['value' => null], 'null'],
    'required named null' => ['nullable-event', ['value' => null], 'null'],
    'positional optional before variadic' => ['variadic-event', ['first'], '["first","fallback",[]]'],
    'named optional before variadic' => ['variadic-event', ['required' => 'first'], '["first","fallback",[]]'],
]);

it('preserves event argument presence', function ($container, $event, $arguments, $expected) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'input-presence/' . $event, '', [
        'eventParams' => json_encode($arguments),
    ]);

    expect(yoyo_update())->toContain('result=' . $expected . '</span>');
})->with('presence containers')->with('present event arguments');

it('still rejects an absent required argument', function ($container, $action, $key, $arguments) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'input-presence/' . $action, '', [
        $key => json_encode($arguments),
    ]);

    expect(fn () => yoyo_update())->toThrow(InvalidArgumentException::class);
})->with('presence containers')->with([
    ['requiredNullable', 'actionArgs', []],
    ['optionalVariadic', 'actionArgs', []],
    ['nullable-event', 'eventParams', ['other' => null]],
]);

it('preserves property input presence and precedence', function ($container, $variables, $request, $expected) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'input-presence', '', $request);

    expect(render('input-presence', $variables))->toContain($expected . '</span>');
})->with('presence containers')->with([
    'default' => [[], [], 'label="default-label"'],
    'caller null' => [['label' => null], [], 'label=null'],
    'request null' => [['label' => 'caller'], ['label' => 'null'], 'label=null'],
    'request wins' => [['label' => null], ['label' => 'request'], 'label="request"'],
    'caller collaborator null' => [['collaborator' => null], [], 'collaborator=null'],
    'request cannot clear collaborator' => [[], ['collaborator' => 'null'], 'collaborator=COMMENT-OBJECT'],
    'request cannot replace caller collaborator null' => [['collaborator' => null], ['collaborator' => '{}'], 'collaborator=null'],
    'dynamic caller value' => [['dynamic' => 42], [], 'dynamic=42'],
    'dynamic request value' => [[], ['dynamic' => 'false'], 'dynamic=false'],
    'dynamic null' => [['dynamic' => null], [], 'dynamic=null'],
]);

it('renders the current dynamic property after an action', function ($container) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'input-presence/changeDynamic', '', ['dynamic' => 'original']);

    expect(yoyo_update())->toContain('dynamic="changed"</span>');
})->with('presence containers');

it('lets lifecycle hooks initialize typed public properties', function ($container) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();

    expect(render('lifecycle-presence'))->toContain('from-initialize from-mount');
})->with('presence containers');

it('preserves non-nullable property values when an input is null', function ($container, $source, $property, $expected) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    $variables = $source === 'caller' ? [$property => null] : [];
    $request = $source === 'request' ? [$property => 'null'] : [];
    mockYoyoGetRequest('http://example.com/', 'typed-input-presence', '', $request);

    expect(render('typed-input-presence', $variables))->toContain($expected . '</span>');
})->with('presence containers')->with(['caller', 'request'])->with([
    'bool' => ['open', 'open=true'],
    'int' => ['limit', 'limit=15'],
    'string' => ['title', 'title="default-title"'],
    'constructor value' => ['current', 'current="constructor-current"'],
    'union' => ['selection', 'selection=25'],
    'collaborator' => ['collaborator', 'collaborator=COMMENT-OBJECT'],
]);

it('still clears properties whose types allow null', function ($container, $source, $property) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    $variables = $source === 'caller' ? [$property => null] : [];
    $request = $source === 'request' ? [$property => 'null'] : [];
    mockYoyoGetRequest('http://example.com/', 'typed-input-presence', '', $request);

    expect(render('typed-input-presence', $variables))->toContain($property . '=null</span>');
})->with('presence containers')->with(['caller', 'request'])->with([
    'nullable union' => ['nullableSelection'],
    'mixed' => ['mixedValue'],
    'untyped' => ['untypedValue'],
]);

it('still applies falsy non-null property inputs', function ($container, $source, $property, $value, $expected) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    $variables = $source === 'caller' ? [$property => $value] : [];
    $request = $source === 'request' ? [$property => json_encode($value)] : [];
    mockYoyoGetRequest('http://example.com/', 'typed-input-presence', '', $request);

    expect(render('typed-input-presence', $variables))->toContain($expected . '</span>');
})->with('presence containers')->with(['caller', 'request'])->with([
    'false' => ['open', false, 'open=false'],
    'zero' => ['limit', 0, 'limit=0'],
    'empty string' => ['title', '', 'title=""'],
]);

it('keeps the property value when request null overrides a caller input', function ($container) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'typed-input-presence', '', ['limit' => 'null']);

    expect(render('typed-input-presence', ['limit' => 42]))->toContain('limit=15</span>');
})->with('presence containers');

it('still rejects other invalid values for typed public properties', function ($container) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'typed-input-presence', '', ['limit' => '{}']);

    expect(fn () => yoyo_update())->toThrow(TypeError::class);
})->with('presence containers');

it('preserves typed defaults when an update sends wire nulls', function ($container) {
    new Yoyo($container === 'native' ? new YoyoContainer() : new IlluminateContainer(new Container()));
    yoyo_view();
    mockYoyoGetRequest('http://example.com/', 'typed-input-presence', '', [
        'open' => 'null',
        'limit' => 'null',
        'title' => 'null',
    ]);

    expect(yoyo_update())
        ->toContain('open=true</span>')
        ->toContain('limit=15</span>')
        ->toContain('title="default-title"</span>');
})->with('presence containers');
