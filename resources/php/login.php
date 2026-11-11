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

ApiBootstrap::init();

$user = new User();
$csrf = new CSRF();
$auth = new Auth();
$request = new ApiRequest();

ApiResponder::run(
    static function () use ($request, $user, $csrf, $auth): void {
        if ($request->method() !== 'POST') {
            HttpJson::result(['error' => 'Invalid request method'], 405);
        }

        $attempt = AuthFlow::attempt($request->input(), $user, $auth, $csrf);
        HttpJson::result($attempt['body'], $attempt['status']);
    },
    'Login request failed',
);
