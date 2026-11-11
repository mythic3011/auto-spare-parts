<?php

declare(strict_types=1);

require_once __DIR__ . '/../class/ResponseHandler.php';
require_once __DIR__ . '/../class/Api/ApiBootstrap.php';
require_once __DIR__ . '/../class/Api/ApiRequest.php';
require_once __DIR__ . '/../class/Api/RouteRegistry.php';
require_once __DIR__ . '/../class/Api/ApiResponder.php';

ApiBootstrap::init();

$request = new ApiRequest();
$registry = new RouteRegistry();

$projectPhpRoot = dirname(__DIR__);

$routes = [
    'orders.dealer_list' => ['script' => $projectPhpRoot . '/order_api.php', 'action' => 'dealer-list'],
    'orders.dealer_get' => ['script' => $projectPhpRoot . '/order_api.php', 'action' => 'dealer-get'],
    'orders.manager_list' => ['script' => $projectPhpRoot . '/order_api.php', 'action' => 'manager-list'],
    'orders.manager_get' => ['script' => $projectPhpRoot . '/order_api.php', 'action' => 'manager-get'],
    'orders.place' => ['script' => $projectPhpRoot . '/order_api.php', 'action' => 'place-order'],
    'orders.update_status' => ['script' => $projectPhpRoot . '/order_api.php', 'action' => 'update-status'],
    'orders.cancel' => ['script' => $projectPhpRoot . '/order_api.php', 'action' => 'cancel'],
    'orders.download' => ['script' => $projectPhpRoot . '/order_api.php', 'action' => 'download'],
    'spare_part.api' => ['script' => $projectPhpRoot . '/api.php', 'action' => ''],
    'spare_part.view' => ['script' => $projectPhpRoot . '/ViewSparePart.php', 'action' => ''],
    'spare_part.detail' => ['script' => $projectPhpRoot . '/SparePartDetail.php', 'action' => ''],
    'cart.api' => ['script' => $projectPhpRoot . '/CartApi.php', 'action' => ''],
    'profile.api' => ['script' => $projectPhpRoot . '/profile_api.php', 'action' => ''],
    'auth.user' => ['script' => $projectPhpRoot . '/user_api.php', 'action' => ''],
    'auth.login' => ['script' => $projectPhpRoot . '/login.php', 'action' => ''],
];

foreach ($routes as $key => $meta) {
    $registry->add(
        $key,
        ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        static fn (): array => $meta,
        false,
    );
}

$registry->alias('dealer-list', 'orders.dealer_list');
$registry->alias('dealer-get', 'orders.dealer_get');
$registry->alias('manager-list', 'orders.manager_list');
$registry->alias('manager-get', 'orders.manager_get');
$registry->alias('place-order', 'orders.place');
$registry->alias('update-status', 'orders.update_status');
$registry->alias('cancel', 'orders.cancel');
$registry->alias('download', 'orders.download');
$registry->alias('spare', 'spare_part.api');
$registry->alias('profile', 'profile.api');
$registry->alias('cart', 'cart.api');
$registry->alias('auth', 'auth.user');

$selectedRoute = null;
ApiResponder::run(
    static function () use ($registry, $request, &$selectedRoute): void {
        $path = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        $pathSegments = $path === '' ? [] : explode('/', $path);

        $routeFromPath = '';
        if (count($pathSegments) >= 2 && $pathSegments[0] === 'api') {
            $normalizedSegments = array_map(
                static fn (string $segment): string => str_replace('-', '_', trim($segment)),
                array_slice($pathSegments, 1),
            );
            $routeFromPath = implode('.', $normalizedSegments);
        }

        $routeKey = $routeFromPath !== '' ? $routeFromPath : trim($request->action());
        if ($routeKey === '') {
            throw new RuntimeException('Route not found', 404);
        }

        $selectedRoute = $registry->dispatch($routeKey, $request->method());
    },
    'API gateway request failed',
);

// Legacy endpoint functions depend on global variables set by their entry script.
// Include in global scope, from the owning directory, to preserve that contract.
if (is_array($selectedRoute)) {
    if ($selectedRoute['action'] !== '') {
        $_GET['action'] = $selectedRoute['action'];
    }
    chdir(dirname($selectedRoute['script']));
    require $selectedRoute['script'];
}
