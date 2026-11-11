<?php

declare(strict_types=1);

require_once './class/Database.php';
require_once './class/Session.php';
require_once './class/User.php';
require_once './class/CSRF.php';
require_once './class/SparePart.php';
require_once './class/ResponseHandler.php';
require_once './class/Api/ApiBootstrap.php';
require_once './class/Api/ApiRequest.php';
require_once './class/Api/ApiResponder.php';

ApiBootstrap::init();

$sparePart = new SparePart();
$request = new ApiRequest();

ApiResponder::run(
    static function () use ($sparePart, $request): void {
        if ($request->method() !== 'POST') {
            throw new RuntimeException('Invalid request method', 405);
        }

        $sparePartListData = $sparePart->getSparePartList();
        if (!$sparePartListData) {
            throw new RuntimeException('SparePartList not found', 404);
        }

        http_response_code(200);
        echo json_encode(['data' => $sparePartListData], JSON_THROW_ON_ERROR);
        exit;
    },
    'View spare part request failed',
);
