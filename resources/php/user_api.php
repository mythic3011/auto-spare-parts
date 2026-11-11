<?php

declare(strict_types=1);

require_once './class/User.php';
require_once './class/CSRF.php';
require_once './class/Auth.php';
require_once './class/AuthFlow.php';
require_once './class/HttpJson.php';
require_once './class/Api/ApiBootstrap.php';
require_once './class/Api/ApiRequest.php';
require_once './class/Api/ApiResponder.php';
require_once './class/Api/RouteRegistry.php';

ApiBootstrap::init();

$user = new User();
$csrf = new CSRF();
$auth = new Auth();
$request = new ApiRequest();
$registry = new RouteRegistry();

$registry->add('auth.login', ['POST'], static function () use ($request, $user, $csrf, $auth): void {
    $attempt = AuthFlow::attempt($request->input(), $user, $auth, $csrf);
    HttpJson::result($attempt['body'], $attempt['status']);
}, false);

$registry->add('auth.logout', ['POST'], static function () use ($auth): void {
    $token = $auth->getBearerToken();
    if (!$token) {
        HttpJson::result(['error' => 'No token provided'], 400);
    }

    HttpJson::result($auth->logout($token));
}, false);

$registry->add('auth.verify_token', ['GET', 'POST'], static function () use ($auth): void {
    $token = $auth->getBearerToken();
    if (!$token) {
        HttpJson::result(['error' => 'No token provided'], 400);
    }

    HttpJson::result(['isValid' => is_array($auth->validateToken($token))]);
}, false);

$registry->alias('login', 'auth.login');
$registry->alias('logout', 'auth.logout');
$registry->alias('verify_token', 'auth.verify_token');

ApiResponder::run(
    static function () use ($request, $registry): void {
        $action = strtolower(trim($request->action()));
        if ($action === '') {
            HttpJson::result(['error' => 'Invalid action'], 400);
        }
        $registry->dispatch($action, $request->method());
    },
    'User API request failed',
);
