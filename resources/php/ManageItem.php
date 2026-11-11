<?php

declare(strict_types=1);

require_once './class/Database.php';
require_once './class/SparePart.php';
require_once './class/StaffAccess.php';
require_once './class/HttpJson.php';
require_once './class/UploadedImage.php';
require_once './class/Api/ApiRequest.php';
require_once './class/Api/ApiBootstrap.php';

ApiBootstrap::init();
StaffAccess::requireSalesManager();

$sparePart = new SparePart();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$action = (string) ($_GET['action'] ?? '');
$act = (string) ($_GET['act'] ?? $_POST['act'] ?? '');

try {
    if ($act === 'listTotal') {
        $rows = Database::getInstance()->query('SELECT COUNT(*) AS total FROM Item');
        $total = is_array($rows) ? (int) ($rows[0]['total'] ?? 0) : 0;
        HttpJson::send(['total' => $total]);
    }

    match ($method) {
        'GET' => handleGet($action, $sparePart),
        'POST' => handlePost($action, $sparePart),
        default => throw new InvalidArgumentException('Invalid request method'),
    };
} catch (InvalidArgumentException $exception) {
    HttpJson::send(['status' => 'error', 'message' => $exception->getMessage()], 400);
} catch (ProductInOrderException $exception) {
    HttpJson::send([
        'status' => 'error', 'code' => 'PRODUCT_IN_ORDER',
        'message' => $exception->getMessage(), 'lockedItemIds' => $exception->itemIds,
    ], 409);
} catch (Throwable $exception) {
    error_log('Manage item failed: ' . $exception->getMessage());
    HttpJson::send(['status' => 'error', 'message' => 'Item request failed'], 400);
}

function handleGet(string $action, SparePart $sparePart): void
{
    if ($action === 'getNewSparePartId') {
        HttpJson::send(['status' => 'success', 'data' => $sparePart->getNewSparePartId()]);
    }

    if ($action === 'list') {
        $sparePartList = $sparePart->getSparePartList();
        HttpJson::send([
            'status' => 'success',
            'data' => $sparePartList,
            'length' => count($sparePartList),
        ]);
    }

    if ($action === 'get' && isset($_GET['id'])) {
        $item = $sparePart->getSparePartById($_GET['id']);
        if ($item === null) {
            HttpJson::send(['status' => 'error', 'message' => 'Item not found']);
        }
        HttpJson::send(['status' => 'success', 'data' => $item]);
    }

    throw new InvalidArgumentException('Invalid GET action');
}

function handlePost(string $action, SparePart $sparePart): void
{
    if ($action === 'update') {
        $id = $_POST['id'] ?? null;
        if (!is_scalar($id) || (string) $id === '') {
            throw new InvalidArgumentException('Item ID is required for update');
        }

        $updateData = $_POST;
        if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $updateData['sparePartImage'] = UploadedImage::store($_FILES['image']);
        }
        unset($updateData['id'], $updateData['action'], $updateData['act']);

        $sparePart->updateSparePart($id, $updateData);
        HttpJson::send(['status' => 'success', 'message' => 'Item updated successfully']);
    }

    try {
        $input = (new ApiRequest())->jsonBody();
    } catch (RuntimeException $exception) {
        throw new InvalidArgumentException($exception->getMessage(), 0, $exception);
    }

    match ($action) {
        'checkId' => respondExists($sparePart, $_GET['id'] ?? null),
        'add' => respondAdded($sparePart, $input),
        'BatchDelete' => respondBatchDelete($sparePart, $input),
        'delete' => respondDelete($sparePart, $input),
        default => throw new InvalidArgumentException('Invalid POST action'),
    };
}

function respondExists(SparePart $sparePart, mixed $id): void
{
    if (!is_scalar($id) || (string) $id === '') {
        HttpJson::send(['error' => 'ID is required for checking'], 400);
    }

    HttpJson::send(['exists' => $sparePart->checkSparePartExists($id)]);
}

/**
 * @param array<string, mixed> $input
 */
function respondAdded(SparePart $sparePart, array $input): void
{
    if (isset($input['image'])) {
        $fileName = UploadedImage::clientName((string) $input['image']);
        if ($fileName !== null) {
            $input['sparePartImage'] = $fileName;
        }
        unset($input['image']);
    }

    $newItem = $sparePart->addSparePart($input);
    HttpJson::send(['status' => 'success', 'message' => 'Item added successfully', 'data' => $newItem]);
}

/**
 * @param array<string, mixed> $input
 */
function respondBatchDelete(SparePart $sparePart, array $input): void
{
    $ids = $input['ids'] ?? null;
    if (!is_array($ids) || $ids === []) {
        throw new InvalidArgumentException('Item IDs are required');
    }

    $sparePart->batchDeleteSparePart($ids);
    HttpJson::send(['status' => 'success', 'message' => 'Items deleted successfully']);
}

/**
 * @param array<string, mixed> $input
 */
function respondDelete(SparePart $sparePart, array $input): void
{
    if (!isset($input['id']) || !is_scalar($input['id']) || (string) $input['id'] === '') {
        throw new InvalidArgumentException('Item ID is required for deletion');
    }

    $sparePart->deleteSparePart($input['id']);
    HttpJson::send(['status' => 'success', 'message' => 'Item deleted successfully']);
}
