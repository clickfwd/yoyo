<?php

use Tests\App\Comment;

use function Tests\mockYoyoGetRequest;
use function Tests\render;
use function Tests\resetYoyoRequest;
use function Tests\yoyo_view;

beforeEach(function () {
    yoyo_view();
});

afterEach(function () {
    resetYoyoRequest();
});

// --- Request data must not populate a class-typed property ---

dataset('decoded property values', [
    'int'    => ['32'],
    'float'  => ['1.5'],
    'bool'   => ['false'],
    'array'  => ['{}'],
    'string' => ['abc'],
]);

it('leaves a class-typed property alone when the request carries its name', function ($value) {
    mockYoyoGetRequest('http://example.com/', 'property-injection', '', ['collaborator' => $value]);

    expect(render('property-injection'))->toContain('collaborator=NULL');
})->with('decoded property values');

it('does not let the request null out a caller-supplied collaborator', function () {
    mockYoyoGetRequest('http://example.com/', 'property-injection', '', ['collaborator' => 'null']);

    $sentinel = new class () extends Comment {
        public function label(): string
        {
            return 'CALLER-COLLABORATOR';
        }
    };

    expect(render('property-injection', ['collaborator' => $sentinel]))->toContain('collaborator=CALLER-COLLABORATOR');
});

// --- Controls: unchanged behaviour ---

it('still lets a caller variable supply a class-typed property', function () {
    mockYoyoGetRequest('http://example.com/', 'property-injection', '', []);

    $sentinel = new class () extends Comment {
        public function label(): string
        {
            return 'CALLER-COLLABORATOR';
        }
    };

    expect(render('property-injection', ['collaborator' => $sentinel]))->toContain('collaborator=CALLER-COLLABORATOR');
});

it('still populates an untyped property from the request', function () {
    mockYoyoGetRequest('http://example.com/', 'property-injection', '', ['label' => 'from-request']);

    expect(render('property-injection'))->toContain('label=from-request');
});

it('still populates a builtin-typed property from the request', function () {
    mockYoyoGetRequest('http://example.com/', 'property-injection', '', ['count' => '7']);

    expect(render('property-injection'))->toContain('count=7');
});

it('still applies property defaults when the request carries nothing', function () {
    mockYoyoGetRequest('http://example.com/', 'property-injection', '', []);

    expect(render('property-injection'))
        ->toContain('collaborator=NULL')
        ->toContain('label=default-label');
});
