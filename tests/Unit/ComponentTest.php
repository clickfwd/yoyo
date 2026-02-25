<?php

use Clickfwd\Yoyo\ComponentResolver;
use Clickfwd\Yoyo\ContainerResolver;
use Clickfwd\Yoyo\Exceptions\ComponentMethodNotFound;
use Clickfwd\Yoyo\Yoyo;

uses()->group('component');

function makeComponent(string $class, string $id = 'test', string $name = 'test'): \Clickfwd\Yoyo\Component
{
    $container = ContainerResolver::resolve();
    $resolver = (new ComponentResolver())($container);

    return new $class($resolver, $id, $name);
}

// --- getListeners ---

it('normalizes numeric listener keys to key=value pairs', function () {
    $component = makeComponent(Tests\App\Yoyo\ComponentWithListeners::class);

    $listeners = $component->getListeners();

    expect($listeners)->toHaveKey('itemAdded', 'onItemAdded');
    expect($listeners)->toHaveKey('refresh', 'refresh');
});

// --- set() method ---

it('sets single view data key', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $component->set('foo', 'bar');

    $ref = new ReflectionClass($component);
    $prop = $ref->getProperty('viewData');
    $prop->setAccessible(true);

    expect($prop->getValue($component))->toMatchArray(['foo' => 'bar']);
});

it('sets multiple view data keys via array', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $component->set(['a' => 1, 'b' => 2]);

    $ref = new ReflectionClass($component);
    $prop = $ref->getProperty('viewData');
    $prop->setAccessible(true);

    expect($prop->getValue($component))->toMatchArray(['a' => 1, 'b' => 2]);
});

it('merges view data on subsequent calls', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $component->set('x', 1);
    $component->set('y', 2);

    $ref = new ReflectionClass($component);
    $prop = $ref->getProperty('viewData');
    $prop->setAccessible(true);

    expect($prop->getValue($component))->toMatchArray(['x' => 1, 'y' => 2]);
});

// --- actionMatches ---

it('matches single action string', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $component->setAction('increment');

    expect($component->actionMatches('increment'))->toBeTrue();
    expect($component->actionMatches('decrement'))->toBeFalse();
});

it('matches action from array', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $component->setAction('increment');

    expect($component->actionMatches(['increment', 'decrement']))->toBeTrue();
    expect($component->actionMatches(['save', 'delete']))->toBeFalse();
});

// --- Computed property caching ---

it('caches computed property value', function () {
    $component = makeComponent(Tests\App\Yoyo\ComputedPropertyCache::class, 'test', 'computed-property-cache');
    $component->boot([], []);

    // First call returns 1 and caches it
    $first = $component->testCount;
    // Second call returns cached value (still 1, not 2)
    $second = $component->testCount;

    expect($first)->toBe(1);
    expect($second)->toBe(1);
});

it('clears all computed property cache', function () {
    $component = makeComponent(Tests\App\Yoyo\ComputedPropertyCache::class, 'test', 'computed-property-cache');
    $component->boot([], []);

    $first = $component->testCount;
    expect($first)->toBe(1);

    $component->forgetComputed();

    // After clearing cache, it recalculates
    $second = $component->testCount;
    expect($second)->toBe(2);
});

it('clears specific computed property cache', function () {
    $component = makeComponent(Tests\App\Yoyo\ComputedPropertyCache::class, 'test', 'computed-property-cache');
    $component->boot([], []);

    $first = $component->testCount;
    expect($first)->toBe(1);

    $component->forgetComputed('testCount');

    $second = $component->testCount;
    expect($second)->toBe(2);
});

it('clears multiple computed property caches via args', function () {
    $component = makeComponent(Tests\App\Yoyo\ComputedPropertyCache::class, 'test', 'computed-property-cache');
    $component->boot([], []);

    $component->testCount;
    $component->forgetComputed('testCount', 'otherKey');

    $second = $component->testCount;
    expect($second)->toBe(2);
});

// --- Computed property with arguments (__call) ---

it('calls computed property with arguments', function () {
    $component = makeComponent(Tests\App\Yoyo\ComponentWithComputedArgs::class, 'test', 'component-with-computed-args');
    $component->boot([], []);

    expect($component->greeting('Alice'))->toBe('Hello, Alice!');
    expect($component->greeting('Bob'))->toBe('Hello, Bob!');
});

it('caches computed property with arguments by arg hash', function () {
    $component = makeComponent(Tests\App\Yoyo\ComponentWithComputedArgs::class, 'test', 'component-with-computed-args');
    $component->boot([], []);

    $first = $component->expensive();
    $second = $component->expensive();

    expect($first)->toBe($second);
});

it('uses different cache keys for different arguments', function () {
    $component = makeComponent(Tests\App\Yoyo\ComponentWithComputedArgs::class, 'test', 'component-with-computed-args');
    $component->boot([], []);

    $resultAlice = $component->greeting('Alice');
    $resultBob = $component->greeting('Bob');

    expect($resultAlice)->toBe('Hello, Alice!');
    expect($resultBob)->toBe('Hello, Bob!');
    expect($resultAlice)->not->toBe($resultBob);
});

it('clears computed property cache with arguments', function () {
    $component = makeComponent(Tests\App\Yoyo\ComponentWithComputedArgs::class, 'test', 'component-with-computed-args');
    $component->boot([], []);

    $first = $component->expensive();
    $component->forgetComputedWithArgs('expensive');

    // After clearing, next call recalculates and returns a different value
    $second = $component->expensive();
    expect($second)->not->toBe($first);
});

// --- __get and __call throw on unknown ---

it('throws ComponentMethodNotFound for unknown property', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class, 'test', 'counter');
    $component->boot([], []);
    $component->nonExistentProperty;
})->throws(ComponentMethodNotFound::class);

it('throws ComponentMethodNotFound for unknown method call', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class, 'test', 'counter');
    $component->boot([], []);
    $component->nonExistentMethod();
})->throws(ComponentMethodNotFound::class);

// --- spinning() ---

it('sets spinning state and returns self', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $result = $component->spinning(true);

    expect($result)->toBe($component);

    $ref = new ReflectionClass($component);
    $prop = $ref->getProperty('spinning');
    $prop->setAccessible(true);

    expect($prop->getValue($component))->toBeTrue();
});

// --- boot() ---

it('sets public properties from variables', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $component->boot(['count' => 42], []);

    expect($component->count)->toBe(42);
});

it('preserves default value when variable not provided', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $component->boot([], []);

    expect($component->count)->toBe(0);
});

// --- getName, getComponentId ---

it('returns component name', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class, 'counter-id', 'counter');
    expect($component->getName())->toBe('counter');
});

it('returns component id', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class, 'counter-id', 'counter');
    expect($component->getComponentId())->toBe('counter-id');
});

// --- getQueryString, getProps ---

it('returns query string configuration', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    expect($component->getQueryString())->toBe(['count']);
});

it('returns props configuration', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    expect($component->getProps())->toBe(['count']);
});

// --- getVariables, getInitialAttributes ---

it('returns variables after boot', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $component->boot(['count' => 5], []);
    expect($component->getVariables())->toBe(['count' => 5]);
});

it('returns initial attributes after boot', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $component->boot([], ['class' => 'my-class']);
    expect($component->getInitialAttributes())->toBe(['class' => 'my-class']);
});

// --- Redirect trait ---

it('stores redirect URL', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    $result = $component->redirect('/some-page');

    expect($result)->toBe($component);
    expect($component->redirectTo)->toBe('/some-page');
});

it('redirect defaults to null', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    expect($component->redirectTo)->toBeNull();
});

// --- Dynamic properties ---

it('returns empty array for getDynamicProperties by default', function () {
    $component = makeComponent(Tests\App\Yoyo\Counter::class);
    expect($component->getDynamicProperties())->toBe([]);
});
