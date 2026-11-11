<?php

declare(strict_types=1);

require_once './class/Database.php';
require_once './class/SparePart.php';
require_once './class/Auth.php';
require_once './class/ResponseHandler.php';
require_once './class/Api/ApiBootstrap.php';
require_once './class/Api/ApiRequest.php';
require_once './class/Api/ApiAuthContext.php';
require_once './class/Api/ApiResponder.php';
require_once './class/Api/RouteRegistry.php';

ApiBootstrap::init();

$request = new ApiRequest();
$authContext = new ApiAuthContext();
$sparePart = new SparePart();
$auth = new Auth();
$registry = new RouteRegistry();

$registry->add(
    'spare_part.list',
    ['GET'],
    static function () use ($sparePart): void {
        sendSuccessResponse($sparePart->getSparePartList());
    },
);
$registry->alias('getSparePartList', 'spare_part.list');

$registry->add(
    'spare_part.get',
    ['POST'],
    static function () use ($request, $sparePart): void {
        $input = $request->jsonBody();
        $id = $input['id'] ?? null;
        if ($id === null || $id === '') {
            throw new RuntimeException('Missing spare part ID', 400);
        }

        sendSuccessResponse($sparePart->getSparePartById($id));
    },
);
$registry->alias('getSparePart', 'spare_part.get');

$registry->add(
    'spare_part.update',
    ['POST'],
    static function () use ($request, $sparePart, $authContext, $auth): void {
        $input = $request->jsonBody();
        $id = $input['id'] ?? null;
        if ($id === null || $id === '') {
            throw new RuntimeException('Missing spare part ID', 400);
        }

        $user = $authContext->requireUser($auth, $request);
        $authContext->requireRole($user, 'SalesManager');
        $updateData = $input;
        unset($updateData['action'], $updateData['id']);

        $result = $sparePart->updateSparePart($id, $updateData);

        sendSuccessResponse([
            'updated' => (bool) $result,
            'updatedBy' => $user['UserID'],
        ]);
    },
);
$registry->alias('updateSparePart', 'spare_part.update');

ApiResponder::run(
    static function () use ($registry, $request, $authContext, $auth): void {
        $method = $request->method();
        $action = $request->action();
        if ($action === '') {
            throw new RuntimeException('Invalid action', 400);
        }

        $route = $registry->routeDefinition($action);
        if ($route === null) {
            throw new RuntimeException('Invalid action', 404);
        }

        if ($route['auth'] === true) {
            $authContext->requireUser($auth, $request);
        }

        $registry->dispatch($action, $method);
    },
    'Spare part request failed',
);

function sendSuccessResponse(mixed $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    echo json_encode(['result' => $data], JSON_THROW_ON_ERROR);
    exit;
}
