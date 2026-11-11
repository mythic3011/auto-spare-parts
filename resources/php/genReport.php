<?php

declare(strict_types=1);

require_once './class/Report.php';
require_once './class/StaffAccess.php';
require_once './class/HttpJson.php';
require_once './class/Api/ApiBootstrap.php';

ApiBootstrap::init();
StaffAccess::requireSalesManager();

$action = (string) ($_GET['action'] ?? '');
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

try {
    if ($method !== 'GET' || $action !== 'getStockReport') {
        throw new InvalidArgumentException('Invalid request');
    }

    HttpJson::send(['status' => 'success', 'data' => (new Report())->getStockReport()]);
} catch (InvalidArgumentException $exception) {
    HttpJson::send(['status' => 'error', 'message' => $exception->getMessage()], 400);
} catch (Throwable $exception) {
    error_log('Report request failed: ' . $exception->getMessage());
    HttpJson::send(['status' => 'error', 'message' => 'Report request failed'], 400);
}
