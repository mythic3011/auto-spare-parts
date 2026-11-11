<?php

declare(strict_types=1);

/**
 * Order API Endpoint
 *
 * Security model:
 * - Never trust username/email from query string for authorization.
 * - Bearer token / auth cookie / server session is the only identity source.
 * - Dealer endpoints are scoped to the authenticated dealer.
 * - Sales manager endpoints require Sales Manager role.
 * - Error responses avoid leaking whether an order exists outside the caller scope.
 */

require_once './class/Database.php';
require_once './class/Order.php';
require_once './class/Auth.php';
require_once './class/Report.php';
require_once './class/ResponseHandler.php';
require_once './class/ValidationHelper.php';
require_once './class/Api/ApiBootstrap.php';
require_once './class/Api/ApiRequest.php';
require_once './class/Api/ApiAuthContext.php';

ApiBootstrap::init();

$order = new Order();
$auth = new Auth();
$report = new Report();
$request = new ApiRequest();
$authContext = new ApiAuthContext();
$method = $request->method();
$action = normalizeAction($request->action());

try {
    $user = authenticateUser();

    match ($method) {
        'GET' => handleGetRequests($action, $order, $user, $report),
        'POST' => handlePostRequests($action, $order, $user),
        default => ResponseHandler::error('Method not allowed', 405),
    };
} catch (Throwable $e) {
    error_log('Order API error: ' . $e->getMessage());

    $statusCode = (int) $e->getCode();

    if ($statusCode < 400 || $statusCode > 599) {
        $statusCode = 500;
    }

    ResponseHandler::error('Order request failed', $statusCode);
}

function normalizeAction(string $action): string
{
    $action = strtolower(trim($action));

    return match ($action) {
        'dealer-get', 'dealer_get', 'dealerget' => 'dealer-get',
        'dealer-list', 'dealer_list', 'dealerlist' => 'dealer-list',
        'manager-get', 'manager_get', 'managerget' => 'manager-get',
        'manager-list', 'manager_list', 'managerlist' => 'manager-list',
        'stock-report', 'stock_report', 'stockreport' => 'stock-report',
        'place-order', 'place_order', 'placeorder' => 'place-order',
        'update-status', 'update_status', 'updatestatus' => 'update-status',
        'download', 'cancel' => $action,
        default => $action,
    };
}

/**
 * @return array<string, mixed>
 */
function authenticateUser(): array
{
    global $auth, $request, $authContext;

    $token = $request->authToken();
    if ($token !== '') {
        $user = $authContext->requireUser($auth, $request);
        return normalizeUserRecord($user);
    }

    $sessionUser = getSessionUser();

    if ($sessionUser !== null) {
        return $sessionUser;
    }

    ResponseHandler::unauthorized('Authentication required');
}

/**
 * @return array<string, mixed>|null
 */
function getSessionUser(): ?array
{
    $sessionUser = $_SESSION['user'] ?? null;

    if (is_array($sessionUser) && getUserIdentifier($sessionUser) !== '') {
        return normalizeUserRecord($sessionUser);
    }

    $identifier = firstNonEmpty([
        $_SESSION['UserID'] ?? null,
        $_SESSION['userID'] ?? null,
        $_SESSION['user_id'] ?? null,
        $_SESSION['username'] ?? null,
        $_SESSION['email'] ?? null,
    ]);

    if ($identifier === '') {
        return null;
    }

    return normalizeUserRecord([
        'UserID' => $identifier,
        'id' => $identifier,
        'username' => $_SESSION['username'] ?? $identifier,
        'email' => $_SESSION['email'] ?? (str_contains($identifier, '@') ? $identifier : ''),
        'role' => $_SESSION['role'] ?? $_SESSION['Role'] ?? 'Dealer',
    ]);
}

/**
 * @param array<string, mixed> $user
 * @return array<string, mixed>
 */
function normalizeUserRecord(array $user): array
{
    $identifier = getUserIdentifier($user);
    $role = (string) ($user['role'] ?? $user['Role'] ?? $user['userRole'] ?? 'Dealer');

    $user['UserID'] = $identifier;
    $user['id'] = $user['id'] ?? $identifier;
    $user['username'] = $user['username'] ?? $identifier;
    $user['email'] = $user['email'] ?? (str_contains($identifier, '@') ? $identifier : '');
    $user['role'] = $role;

    return $user;
}

/**
 * @param array<string, mixed> $user
 */
function getUserIdentifier(array $user): string
{
    return firstNonEmpty([
        $user['UserID'] ?? null,
        $user['userID'] ?? null,
        $user['userId'] ?? null,
        $user['id'] ?? null,
        $user['username'] ?? null,
        $user['email'] ?? null,
    ]);
}

/**
 * @param array<int, mixed> $values
 */
function firstNonEmpty(array $values): string
{
    foreach ($values as $value) {
        $stringValue = trim((string) ($value ?? ''));

        if ($stringValue !== '') {
            return $stringValue;
        }
    }

    return '';
}

/**
 * @param array<string, mixed> $user
 */
function requireSalesManager(array $user): void
{
    $role = (string) ($user['role'] ?? $user['Role'] ?? $user['UserRole'] ?? '');

    if (!RoleName::is($role, 'salesmanager')) {
        ResponseHandler::error('Sales manager permission required', 403);
    }
}

/**
 * @param array<string, mixed> $user
 */
function getAuthenticatedUserId(array $user): string
{
    $identifier = getUserIdentifier($user);

    if ($identifier === '') {
        ResponseHandler::unauthorized('Authentication required');
    }

    return $identifier;
}

/**
 * @param array<string, mixed> $user
 */
function handleGetRequests(string $action, Order $order, array $user, Report $report): void
{
    match ($action) {
        'dealer-list' => handleDealerOrderList($order, $user),
        'dealer-get' => handleGetOrderForDealer($order, $user),
        'manager-list' => handleManagerOrderList($order, $user),
        'manager-get' => handleGetOrderForManager($order, $user),
        'stock-report' => handleStockReport($report, $user),
        'download' => handleDownloadOrderInvoice($order, $user, $report),
        default => ResponseHandler::error('Invalid GET action', 400),
    };
}

/**
 * @param array<string, mixed> $user
 */
function handleDealerOrderList(Order $order, array $user): void
{
    $dealerID = getAuthenticatedUserId($user);

    try {
        $orders = $order->getOrderListByDealer($dealerID);
        ResponseHandler::success([
            'orders' => is_array($orders) ? $orders : [],
        ], 'Orders retrieved successfully');
    } catch (Throwable $e) {
        error_log('Failed to retrieve dealer orders: ' . $e->getMessage());
        ResponseHandler::error('Failed to retrieve orders', 400);
    }
}

/**
 * @param array<string, mixed> $user
 */
function handleManagerOrderList(Order $order, array $user): void
{
    requireSalesManager($user);

    try {
        $orders = $order->getOrderListBySalesManager();
        ResponseHandler::success([
            'orders' => is_array($orders) ? $orders : [],
        ], 'Orders retrieved successfully');
    } catch (Throwable $e) {
        error_log('Failed to retrieve manager orders: ' . $e->getMessage());
        ResponseHandler::error('Failed to retrieve orders', 400);
    }
}

/**
 * @param array<string, mixed> $user
 */
function handleGetOrderForDealer(Order $order, array $user): void
{
    $orderID = getOrderIdFromRequest();
    $dealerID = getAuthenticatedUserId($user);

    try {
        $orderData = $order->getOrderByIdAndDealer((string) $orderID, $dealerID);

        if ($orderData === null) {
            ResponseHandler::notFound('Order not found');
        }

        ResponseHandler::success($orderData, 'Order retrieved successfully');
    } catch (Throwable $e) {
        error_log('Failed to retrieve dealer order: ' . $e->getMessage());
        ResponseHandler::error('Failed to retrieve order', 400);
    }
}

/**
 * @param array<string, mixed> $user
 */
function handleGetOrderForManager(Order $order, array $user): void
{
    requireSalesManager($user);
    $orderID = getOrderIdFromRequest();

    try {
        $orderData = $order->getOrderByIdAndSalesManager((string) $orderID);

        if ($orderData === null) {
            ResponseHandler::notFound('Order not found');
        }

        ResponseHandler::success($orderData, 'Order retrieved successfully');
    } catch (Throwable $e) {
        error_log('Failed to retrieve manager order: ' . $e->getMessage());
        ResponseHandler::error('Failed to retrieve order', 400);
    }
}

/**
 * @param array<string, mixed> $user
 */
function handleStockReport(Report $report, array $user): void
{
    requireSalesManager($user);

    try {
        ResponseHandler::success($report->getStockReport(), 'Stock report retrieved successfully');
    } catch (Throwable $e) {
        error_log('Failed to generate stock report: ' . $e->getMessage());
        ResponseHandler::error('Failed to generate report', 400);
    }
}

/**
 * @param array<string, mixed> $user
 */
function handleDownloadOrderInvoice(Order $order, array $user, Report $report): void
{
    $orderID = getOrderIdFromRequest();
    $role = (string) ($user['role'] ?? $user['Role'] ?? '');

    try {
        if (stripos($role, 'Sales Manager') !== false) {
            $orderData = $order->getOrderByIdAndSalesManager((string) $orderID);
        } else {
            $orderData = $order->getOrderByIdAndDealer((string) $orderID, getAuthenticatedUserId($user));
        }

        if ($orderData === null) {
            ResponseHandler::notFound('Order not found');
        }

        if (!method_exists($report, 'generateOrderInvoice')) {
            ResponseHandler::error('Invoice download is not available', 501);
        }

        $invoiceContent = $report->generateOrderInvoice($orderData);

        if (!is_string($invoiceContent) || $invoiceContent === '') {
            ResponseHandler::error('Failed to generate invoice', 500);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="order-' . $orderID . '.pdf"');
        header('Content-Length: ' . strlen($invoiceContent));
        echo $invoiceContent;
        exit;
    } catch (Throwable $e) {
        error_log('Failed to download order invoice: ' . $e->getMessage());
        ResponseHandler::error('Failed to download invoice', 400);
    }
}

/**
 * @param array<string, mixed> $user
 */
function handlePostRequests(string $action, Order $order, array $user): void
{
    $input = getJsonInput();

    match ($action) {
        'place-order' => handlePlaceOrder($order, $input, $user),
        'update-status' => handleUpdateOrderStatus($order, $input, $user),
        'cancel' => handleCancelOrder($order, $input, $user),
        default => ResponseHandler::error('Invalid POST action', 400),
    };
}

/**
 * @return array<string, mixed>
 */
function getJsonInput(): array
{
    $rawInput = file_get_contents('php://input') ?: '';

    if ($rawInput === '') {
        return [];
    }

    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        ResponseHandler::error('Invalid JSON input', 400);
    }

    return $input;
}

/**
 * @param array<string, mixed> $input
 * @param array<string, mixed> $user
 */
function handlePlaceOrder(Order $order, array $input, array $user): void
{
    if (!array_key_exists('cart', $input)) {
        $validator = new ValidationHelper();
        $validator->required('cart', null);
        ResponseHandler::validationError($validator->getErrors());
    }

    if (!is_array($input['cart']) || empty($input['cart'])) {
        ResponseHandler::error('Cart cannot be empty', 400);
    }

    $input['userId'] = getAuthenticatedUserId($user);

    try {
        $orderID = $order->placeOrder($input, $user);
        ResponseHandler::created(['id' => $orderID], 'Order placed successfully');
    } catch (Throwable $e) {
        error_log('Order placement error: ' . $e->getMessage());
        ResponseHandler::error('Failed to place order', 400);
    }
}

/**
 * @param array<string, mixed> $input
 * @param array<string, mixed> $user
 */
function handleUpdateOrderStatus(Order $order, array $input, array $user): void
{
    requireSalesManager($user);

    $rawId = $input['id'] ?? $input['orderId'] ?? $_GET['id'] ?? $_GET['orderId'] ?? null;
    $status = trim((string) ($input['status'] ?? ''));

    $validator = new ValidationHelper();
    $validator->required('id', is_scalar($rawId) ? trim((string) $rawId) : '');
    $validator->required('status', $status);
    $validator->in('status', $status, ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled']);

    if (!$validator->passed()) {
        ResponseHandler::validationError($validator->getErrors());
    }

    $orderId = normalizePositiveIntInput($rawId, 'Order ID');

    try {
        $updatedOrder = $order->updateOrderStatus((string) $orderId, $status);

        if ($updatedOrder === null) {
            ResponseHandler::notFound('Order not found');
        }

        ResponseHandler::success($updatedOrder, 'Order status updated successfully');
    } catch (Throwable $e) {
        error_log('Failed to update order status: ' . $e->getMessage());
        ResponseHandler::error('Failed to update order', 400);
    }
}

/**
 * @param array<string, mixed> $input
 * @param array<string, mixed> $user
 */
function handleCancelOrder(Order $order, array $input, array $user): void
{
    $orderID = normalizePositiveIntInput($input['id'] ?? $input['orderId'] ?? $_GET['id'] ?? $_GET['orderId'] ?? null, 'Order ID');
    $role = (string) ($user['role'] ?? $user['Role'] ?? '');

    try {
        if (!preg_match('/sales\s*manager/i', $role)) {
            $existingOrder = $order->getOrderByIdAndDealer((string) $orderID, getAuthenticatedUserId($user));

            if ($existingOrder === null) {
                ResponseHandler::notFound('Order not found');
            }
        }

        $cancelledOrder = $order->cancelOrder((string) $orderID);

        if ($cancelledOrder === null) {
            ResponseHandler::notFound('Order not found');
        }

        ResponseHandler::success($cancelledOrder, 'Order cancelled successfully');
    } catch (Throwable $e) {
        error_log('Failed to cancel order: ' . $e->getMessage());
        ResponseHandler::error('Failed to cancel order', 400);
    }
}

function getOrderIdFromRequest(): int
{
    return normalizePositiveIntInput($_GET['id'] ?? $_GET['orderId'] ?? null, 'Order ID');
}

function normalizePositiveIntInput(mixed $value, string $label): int
{
    if (!is_scalar($value)) {
        ResponseHandler::error($label . ' must be a positive integer', 400);
    }

    $value = trim((string) $value);

    $normalized = ltrim($value, '0');
    $integer = filter_var($normalized, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($value === '' || !ctype_digit($value) || $integer === false) {
        ResponseHandler::error($label . ' must be a positive integer', 400);
    }

    return $integer;
}
