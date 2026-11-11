<?php

declare(strict_types=1);

require_once __DIR__ . '/class/config.php';
require_once __DIR__ . '/class/HttpPreflight.php';
require_once __DIR__ . '/class/Session.php';

Session::begin();

$allowedOrigins = (defined('CONFIG') && is_array(CONFIG['origins'] ?? null))
    ? CONFIG['origins']
    : [];
$preflightMaxAge = defined('CONFIG') ? (int) (CONFIG['cors_max_age'] ?? 600) : 600;

if (HttpPreflight::isOptions()) {
    HttpPreflight::respond($allowedOrigins, $preflightMaxAge);
}

HttpPreflight::apply($allowedOrigins, $preflightMaxAge);
