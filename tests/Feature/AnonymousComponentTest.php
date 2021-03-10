<?php

use Clickfwd\Yoyo\Yoyo;
use Clickfwd\Yoyo\ComponentManager;
use Clickfwd\Yoyo\Exceptions\ComponentNotFound;
use function Tests\render;
use function Tests\update;
use function Tests\yoyo_view;

beforeAll(function () {
    yoyo_view();
});

it('throws exception when template not found', function () {
    render('random');
})->throws(ComponentNotFound::class);

it('renders anonymous component', function () {
    expect(render('foo'))->toContain('default foo');
});

it('updates anonymous component', function () {
    expect(update('foo'))->toContain('default bar');
});

it('loads anonymous component with a registered alias', function () {
    \Clickfwd\Yoyo\Yoyo::registerComponent('awesome', 'registered-anon');
    expect(render('awesome'))->toContain('id="registered-anon"');
});

it('renders anonymous component in sub-directory', function () {
    expect(render('account.login'))->toContain('app/resources/views/yoyo/account/login.php');
});
