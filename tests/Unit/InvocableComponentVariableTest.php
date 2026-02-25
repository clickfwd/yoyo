<?php

use Clickfwd\Yoyo\InvocableComponentVariable;

it('invokes the callable when used as a function', function () {
    $variable = new InvocableComponentVariable(function () {
        return 'hello';
    });

    expect($variable())->toBe('hello');
});

it('converts to string by invoking the callable', function () {
    $variable = new InvocableComponentVariable(function () {
        return 'world';
    });

    expect((string) $variable)->toBe('world');
});

it('delegates property access to the invoked result', function () {
    $obj = new stdClass();
    $obj->name = 'test';

    $variable = new InvocableComponentVariable(function () use ($obj) {
        return $obj;
    });

    expect($variable->name)->toBe('test');
});

it('delegates method calls to the invoked result', function () {
    $obj = new class () {
        public function greet()
        {
            return 'hi';
        }
    };

    $variable = new InvocableComponentVariable(function () use ($obj) {
        return $obj;
    });

    expect($variable->greet())->toBe('hi');
});

it('passes arguments to delegated method calls', function () {
    $obj = new class () {
        public function add($a, $b)
        {
            return $a + $b;
        }
    };

    $variable = new InvocableComponentVariable(function () use ($obj) {
        return $obj;
    });

    expect($variable->add(2, 3))->toBe(5);
});

it('handles numeric return values in toString', function () {
    $variable = new InvocableComponentVariable(function () {
        return 42;
    });

    expect((string) $variable)->toBe('42');
});
