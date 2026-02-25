<?php

use Clickfwd\Yoyo\QueryString;
use Clickfwd\Yoyo\Request;
use Clickfwd\Yoyo\Yoyo;

beforeEach(function () {
    $request = new Request();
    Yoyo::getInstance()->bindRequest($request);
    $request->mock([], ['HTTP_HX_CURRENT_URL' => 'http://example.com/page']);
});

it('returns only declared query string keys', function () {
    $qs = new QueryString(
        ['count' => 0, 'name' => 'default'],
        ['count' => 5, 'name' => 'test', 'extra' => 'ignored'],
        ['count', 'name']
    );

    $params = $qs->getQueryParams();

    expect($params)->toHaveKey('count');
    expect($params)->toHaveKey('name');
    expect($params)->not->toHaveKey('extra');
});

it('merges defaults with new values in getQueryParams', function () {
    $qs = new QueryString(
        ['count' => 0],
        ['count' => 5],
        ['count']
    );

    expect($qs->getQueryParams())->toBe(['count' => 5]);
});

it('returns empty array when no URL is available', function () {
    $request = new Request();
    Yoyo::getInstance()->bindRequest($request);
    $request->mock([], []);

    $qs = new QueryString(['count' => 0], ['count' => 5], ['count']);

    expect($qs->getPageQueryParams())->toBe([]);
});

it('removes params matching default values from page query params', function () {
    $qs = new QueryString(
        ['count' => 0],
        ['count' => 0],
        ['count']
    );

    $params = $qs->getPageQueryParams();

    expect($params)->not->toHaveKey('count');
});

it('removes empty string values from page query params', function () {
    $qs = new QueryString(
        ['filter' => ''],
        ['filter' => ''],
        ['filter']
    );

    $params = $qs->getPageQueryParams();

    expect($params)->not->toHaveKey('filter');
});

it('preserves existing query string params from URL', function () {
    $request = new Request();
    Yoyo::getInstance()->bindRequest($request);
    $request->mock([], ['HTTP_HX_CURRENT_URL' => 'http://example.com/page?existing=value']);

    $qs = new QueryString(
        ['count' => 0],
        ['count' => 5],
        ['count']
    );

    $params = $qs->getPageQueryParams();

    expect($params)->toHaveKey('existing');
    expect($params['existing'])->toBe('value');
    expect($params)->toHaveKey('count');
    expect($params['count'])->toBe(5);
});

it('keeps params that differ from defaults', function () {
    $qs = new QueryString(
        ['count' => 0],
        ['count' => 10],
        ['count']
    );

    $params = $qs->getPageQueryParams();

    expect($params)->toHaveKey('count');
    expect($params['count'])->toBe(10);
});

it('filters new values to only declared keys in page query params', function () {
    $qs = new QueryString(
        ['count' => 0],
        ['count' => 5, 'secret' => 'hidden'],
        ['count']
    );

    $params = $qs->getPageQueryParams();

    expect($params)->not->toHaveKey('secret');
});
