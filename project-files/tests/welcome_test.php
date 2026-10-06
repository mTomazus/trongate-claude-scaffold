<?php
/**
 * Tests for the welcome module shipped by trongate-init.
 * Treat this as the worked example: copy the shape, delete the tests
 * once the welcome module is replaced by real pages.
 */

test('homepage returns 200', function () {
    $res = tg_get('/');
    assert_status(200, $res);
});

test('homepage renders the under-construction page', function () {
    $res = tg_get('/');
    assert_body_contains('Coming Soon', $res);
});

test('homepage loads the Trongate stylesheet', function () {
    $res = tg_get('/');
    assert_body_contains('css/trongate.css', $res);
});

test('Trongate stylesheet is actually being served', function () {
    $res = tg_get('/css/trongate.css');
    assert_status(200, $res);
});

test('component stylesheet is loaded and served', function () {
    assert_body_contains('css/app.css', tg_get('/'));

    $res = tg_get('/css/app.css');
    assert_status(200, $res);
    assert_body_contains('.badge', $res);
});

test('component showcase is reachable', function () {
    $res = tg_get('/welcome/components');
    assert_status(200, $res);
    assert_body_contains('Component Showcase', $res);
});

test('unknown module returns 404', function () {
    // Keep '_module' out of this URL. Trongate substring-matches
    // MODULE_ASSETS_TRIGGER against each segment (engine/Core.php), so any
    // segment containing it is routed to asset serving and 403s instead.
    $res = tg_get('/no_such_page');
    assert_status(404, $res);
});

/*
 * Requests share a cookie jar for the whole run, so a login flow reads:
 *
 *   test('desk staff can reach the dashboard', function () {
 *       $login = tg_post('/members/submit_login', ['username' => 'x', 'password' => 'y']);
 *       assert_redirects_to('/dashboard', $login);
 *
 *       $dashboard = tg_get('/dashboard');
 *       assert_status(200, $dashboard);
 *       assert_body_lacks('Please log in', $dashboard);
 *   });
 */
