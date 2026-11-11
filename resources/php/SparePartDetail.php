<?php

declare(strict_types=1);

require_once './class/SparePart.php';
require_once './class/Api/ApiBootstrap.php';
require_once './class/Api/ApiRequest.php';
require_once './class/Api/ApiResponder.php';

ApiBootstrap::init();

$sparePart = new SparePart();
$request = new ApiRequest();

ApiResponder::run(
    static function () use ($sparePart, $request): void {
        if ($request->method() !== 'POST') {
            sendErrorResponse('Invalid request method', 405);
        }

        $data = $request->jsonBody();
        $sparePartId = $data['sparePartId'] ?? null;

        if (!is_scalar($sparePartId)) {
            sendErrorResponse('Invalid spare part id', 400);
        }

        $sparePartId = trim((string) $sparePartId);
        if ($sparePartId === '' || !ctype_digit($sparePartId) || (int) $sparePartId <= 0) {
            sendErrorResponse('Invalid spare part id', 400);
        }

        $sparePartData = $sparePart->getSparePart((int) $sparePartId);
        if (!$sparePartData) {
            sendErrorResponse('Spare part not found', 404);
        }

        $response = [
            'id' => $sparePartData['sparePartNum'] ?? '',
            'name' => $sparePartData['sparePartName'] ?? '',
            'category' => $sparePartData['sparePartCategory'] ?? '',
            'imageUrl' => $sparePartData['sparePartImage'] ?? '',
            'description' => $sparePartData['sparePartDescription'] ?? '',
            'sparePartNum' => $sparePartData['sparePartNum'] ?? '',
            'sparePartName' => $sparePartData['sparePartName'] ?? '',
            'sparePartCategory' => $sparePartData['sparePartCategory'] ?? '',
            'sparePartImage' => $sparePartData['sparePartImage'] ?? '',
            'sparePartDescription' => $sparePartData['sparePartDescription'] ?? '',
            'weight' => $sparePartData['weight'] ?? '',
            'stockItemQty' => $sparePartData['stockItemQty'] ?? 0,
            'price' => $sparePartData['price'] ?? 0,
            'countryOfOrigin' => $sparePartData['countryOfOrigin'] ?? 'N/A',
            'brand' => $sparePartData['brand'] ?? 'N/A',
        ];

        sendJsonResponse($response);
    },
    'Spare part detail request failed',
);

function sendJsonResponse(array $data, int $statusCode = 200): never
{
    sendResponse(
        [
            'status' => 'success',
            'data' => $data,
        ],
        $statusCode,
    );
}

function sendErrorResponse(string $message, int $statusCode = 400): never
{
    sendResponse(
        [
            'status' => 'error',
            'error' => $message,
            'message' => $message,
        ],
        $statusCode,
    );
}

function sendResponse(array $payload, int $statusCode = 200): never
{
    if ($statusCode < 100 || $statusCode > 599) {
        $statusCode = 500;
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}
