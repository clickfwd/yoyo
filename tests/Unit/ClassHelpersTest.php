<?php

use Clickfwd\Yoyo\ClassHelpers;
use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\ComponentResolver;
use Clickfwd\Yoyo\Yoyo;
use Tests\App\Post;
use Tests\App\Yoyo\ComponentWithTrait;
use Tests\App\Yoyo\CompositeTypeParams;
use Tests\App\Yoyo\ComputedProperty;
use Tests\App\Yoyo\Counter;

function resolveComponent(string $class = Counter::class, string $name = 'counter'): Component
{
    $resolver = new ComponentResolver(Yoyo::getInstance());

    return new $class($resolver, 'test-'.$name, $name);
}

beforeEach(function () {
    ClassHelpers::flushCache();
});

it('returns public properties excluding base class', function () {
    $component = resolveComponent();
    $props = ClassHelpers::getPublicProperties($component, Component::class);
    expect($props)->toContain('count');
    expect($props)->not->toContain('yoyo_id');
});

it('returns same result on repeated calls', function () {
    $component = resolveComponent();
    $first = ClassHelpers::getPublicProperties($component, Component::class);
    $second = ClassHelpers::getPublicProperties($component, Component::class);
    expect($first)->toEqual($second);
});

it('returns default public vars', function () {
    $component = resolveComponent();
    $defaults = ClassHelpers::getDefaultPublicVars($component, Component::class);
    expect($defaults)->toHaveKey('count');
    expect($defaults['count'])->toBe(0);
});

it('returns current public vars after mutation', function () {
    $component = resolveComponent();
    $component->count = 5;
    $vars = ClassHelpers::getPublicVars($component, Component::class);
    expect($vars['count'])->toBe(5);
});

it('returns public methods excluding base class', function () {
    $methods = ClassHelpers::getPublicMethods(Counter::class, ['render']);
    expect($methods)->toContain('increment');
});

it('discovers traits recursively', function () {
    $traits = ClassHelpers::classUsesRecursive(ComponentWithTrait::class);
    expect($traits)->not->toBeEmpty();
});

it('returns class basename from FQCN', function () {
    expect(ClassHelpers::classBasename(Counter::class))->toBe('Counter');
});

it('detects non-private methods', function () {
    expect(ClassHelpers::methodIsPrivate(Counter::class, 'increment'))->toBeFalse();
});

it('gets method parameter names', function () {
    $names = ClassHelpers::getMethodParameterNames(Counter::class, 'increment');
    expect($names)->toBeArray();
});

it('returns method parameters with types', function () {
    $params = ClassHelpers::getMethodParametersWithTypes(Counter::class, 'increment');
    expect($params)->toHaveKey('typed');
    expect($params)->toHaveKey('regular');
});

it('detects variadic parameters', function () {
    expect(ClassHelpers::methodHasVariadicParameter(Counter::class, 'increment'))->toBeFalse();
});

// --- Caching tests ---

it('returns cached result on second call (strict identity)', function () {
    $component = resolveComponent();

    $first = ClassHelpers::getPublicProperties($component, Component::class);
    $second = ClassHelpers::getPublicProperties($component, Component::class);

    expect($first)->toBe($second);
});

it('separates cache by class name', function () {
    $counter = resolveComponent(Counter::class, 'counter');
    $computed = resolveComponent(ComputedProperty::class, 'computed-property');

    $counterProps = ClassHelpers::getPublicProperties($counter, Component::class);
    $computedProps = ClassHelpers::getPublicProperties($computed, Component::class);

    expect($counterProps)->not->toEqual($computedProps);
});

it('flushCache clears all caches', function () {
    $component = resolveComponent();
    ClassHelpers::getPublicProperties($component, Component::class);
    ClassHelpers::getDefaultPublicVars($component, Component::class);
    ClassHelpers::getPublicMethods(Counter::class, ['render']);
    ClassHelpers::classUsesRecursive(ComponentWithTrait::class);

    ClassHelpers::flushCache();

    // After flush, a new call should still return correct results
    $props = ClassHelpers::getPublicProperties($component, Component::class);
    expect($props)->toContain('count');
});

it('caches getDefaultPublicVars result', function () {
    $component = resolveComponent();

    $first = ClassHelpers::getDefaultPublicVars($component, Component::class);
    $second = ClassHelpers::getDefaultPublicVars($component, Component::class);

    expect($first)->toBe($second);
});

it('caches classUsesRecursive result', function () {
    $first = ClassHelpers::classUsesRecursive(ComponentWithTrait::class);
    $second = ClassHelpers::classUsesRecursive(ComponentWithTrait::class);

    expect($first)->toBe($second);
});

it('caches getPublicMethods result', function () {
    $first = ClassHelpers::getPublicMethods(Counter::class, ['render']);
    $second = ClassHelpers::getPublicMethods(Counter::class, ['render']);

    expect($first)->toBe($second);
});

// --- Composite (union / intersection) parameter types ---
//
// The classifier must mirror Laravel's own rule: only a ReflectionNamedType that is
// not builtin counts as a container-resolved slot. Union and intersection types have
// no isBuiltin() method, so touching them unguarded is a fatal Error, not an exception.

it('classifies a union of class and builtin without fatalling', function () {
    $params = ClassHelpers::getMethodParametersWithTypes(CompositeTypeParams::class, 'unionOfClassAndBuiltin');

    expect($params['typed'])->toBeEmpty()
        ->and(array_column($params['regular'], 'name'))->toContain('post');
});

it('classifies a union of builtins without fatalling', function () {
    $params = ClassHelpers::getMethodParametersWithTypes(CompositeTypeParams::class, 'unionOfBuiltins');

    expect($params['typed'])->toBeEmpty()
        ->and(array_column($params['regular'], 'name'))->toContain('value');
});

it('classifies an intersection type without fatalling', function () {
    $params = ClassHelpers::getMethodParametersWithTypes(CompositeTypeParams::class, 'intersection');

    expect($params['typed'])->toBeEmpty()
        ->and(array_column($params['regular'], 'name'))->toContain('both');
});

it('still treats a nullable class as a container-resolved slot', function () {
    $params = ClassHelpers::getMethodParametersWithTypes(CompositeTypeParams::class, 'nullableClass');

    expect(array_column($params['typed'], 'name'))->toContain('post')
        ->and($params['regular'])->toBeEmpty();
});

// --- Builtin and untyped parameters are caller-supplied, never container-resolved ---

it('classifies builtin and untyped parameters as caller-supplied', function () {
    $params = ClassHelpers::getMethodParametersWithTypes(CompositeTypeParams::class, 'builtinsAndUntyped');

    expect($params['typed'])->toBeEmpty()
        ->and(array_column($params['regular'], 'name'))->toBe(['i', 's', 'b', 'a', 'untyped']);
});

it('returns builtin and untyped parameter names, and omits container slots', function () {
    expect(ClassHelpers::getMethodParameterNames(CompositeTypeParams::class, 'builtinsAndUntyped'))
        ->toBe(['i', 's', 'b', 'a', 'untyped'])
        ->and(ClassHelpers::getMethodParameterNames(CompositeTypeParams::class, 'classSlot'))
        ->toBe([]);
});

it('names a container slot with its resolved type', function () {
    $params = ClassHelpers::getMethodParametersWithTypes(CompositeTypeParams::class, 'classSlot');

    expect(array_column($params['typed'], 'name'))->toBe(['post'])
        ->and($params['typed'][0]['type'])->toBe(Post::class)
        ->and($params['regular'])->toBeEmpty();
});
