<?php

use Clickfwd\Yoyo\ClassHelpers;
use Clickfwd\Yoyo\Component;
use Clickfwd\Yoyo\ComponentResolver;
use Clickfwd\Yoyo\Yoyo;
use Tests\App\Yoyo\ComponentWithTrait;
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
