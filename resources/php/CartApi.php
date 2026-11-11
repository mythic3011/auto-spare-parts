<?php
declare(strict_types=1);

require_once './class/Cart.php';
require_once './class/SparePart.php';
require_once './class/Api/ApiBootstrap.php';
require_once './class/Api/ApiRequest.php';
require_once './class/Api/ApiResponder.php';
require_once './class/Api/RouteRegistry.php';

ApiBootstrap::init();

$request = new ApiRequest();
$method = $request->method();
$action = normalizeAction($request->action());
$cart = new Cart();
$registry = new RouteRegistry();

$registry->add('cart.create', ['POST'], static function (array $input) use ($cart): void {
    handleCreateCart($input, $cart);
}, false);
$registry->add('cart.get', ['GET', 'POST'], static function (array $input) use ($cart): void {
    handleGetCart($input, $cart);
}, false);
$registry->add('cart.add', ['POST'], static function (array $input) use ($cart): void {
    handleAddItem($input, $cart);
}, false);
$registry->add('cart.update', ['POST'], static function (array $input) use ($cart): void {
    handleUpdateQuantity($input, $cart);
}, false);
$registry->add('cart.remove', ['POST'], static function (array $input) use ($cart): void {
    handleRemoveItem($input, $cart);
}, false);
$registry->add('cart.buynow', ['POST'], static function (array $input) use ($cart): void {
    handleBuyNow($input, $cart);
}, false);
$registry->add('cart.clear', ['POST'], static function (array $input) use ($cart): void {
    handleClearCart($input, $cart);
}, false);
$registry->add('cart.checkstock', ['GET', 'POST'], static function (array $input) use ($cart): void {
    handleCheckStock($input, $cart);
}, false);

$registry->alias('create', 'cart.create');
$registry->alias('get', 'cart.get');
$registry->alias('add', 'cart.add');
$registry->alias('update', 'cart.update');
$registry->alias('remove', 'cart.remove');
$registry->alias('buynow', 'cart.buynow');
$registry->alias('clear', 'cart.clear');
$registry->alias('checkstock', 'cart.checkstock');

ApiResponder::run(
    static function () use ($request, $registry, $method, $action): void {
        $input = readRequestInput($request, $method);
        if ($action === '') {
            throw new InvalidArgumentException('Invalid action', 400);
        }
        $registry->dispatch($action, $method, $input);
    },
    'Cart API request failed',
);

function normalizeAction(mixed $action): string
{
    $action = strtolower(trim((string) $action));
    $aliases = [
        'createcart' => 'create',
        'newcart' => 'create',
        'getcart' => 'get',
        'list' => 'get',
        'view' => 'get',
        'additem' => 'add',
        'addtocart' => 'add',
        'updateitem' => 'update',
        'updatequantity' => 'update',
        'removeitem' => 'remove',
        'delete' => 'remove',
        'clearcart' => 'clear',
        'check-stock' => 'checkstock',
    ];

    return $aliases[$action] ?? $action;
}

/**
 * @return array<string, mixed>
 */
function readRequestInput(ApiRequest $request, string $method): array
{
    $queryInput = $_GET;
    unset($queryInput['action']);

    if ($method !== 'POST') {
        return $queryInput;
    }
    $jsonInput = $request->jsonBody();
    if ($jsonInput === []) {
        return array_merge($queryInput, $_POST);
    }
    return array_merge($queryInput, $jsonInput);
}

function getRequiredPositiveInt(array $input, string $key, string $label): int
{
    $value = $input[$key] ?? null;

    if (!is_scalar($value)) {
        throw new InvalidArgumentException($label . ' is required', 400);
    }

    $value = trim((string) $value);

    if ($value === '' || !ctype_digit($value) || (int) $value <= 0) {
        throw new InvalidArgumentException($label . ' must be a positive integer', 400);
    }

    return (int) $value;
}

function getRequiredNonNegativeInt(array $input, string $key, string $label): int
{
    $value = $input[$key] ?? null;

    if (!is_scalar($value)) {
        throw new InvalidArgumentException($label . ' is required', 400);
    }

    $value = trim((string) $value);

    if ($value === '' || !ctype_digit($value) || (int) $value < 0) {
        throw new InvalidArgumentException($label . ' must be a non-negative integer', 400);
    }

    return (int) $value;
}

function getOptionalString(array $input, string $key): string
{
    $value = $input[$key] ?? '';
    return is_scalar($value) ? trim((string) $value) : '';
}

function handleCreateCart(array $input, Cart $cart): void
{
    $userId = getOptionalString($input, 'userId');

    if ($userId === '') {
        throw new InvalidArgumentException('User ID is required', 400);
    }

    sendResponse([
        'status' => 'success',
        'message' => 'Cart created successfully',
        'owner' => $userId,
        'cartId' => $cart->getCartId(),
        'cartItems' => [],
        'cartTotal' => 0.0,
    ]);
}

function handleGetCart(array $input, Cart $cart): void
{
    sendResponse(getCartResponse($cart));
}

function handleAddItem(array $input, Cart $cart): void
{
    $partId = getRequiredPositiveInt($input, 'partId', 'Part ID');
    $quantity = getRequiredPositiveInt($input, 'quantity', 'Quantity');

    $stockInfo = $cart->checkStock($partId);

    if (empty($stockInfo['isAvailable'])) {
        throw new RuntimeException('Item is out of stock', 409);
    }

    if (isset($stockInfo['stockItemQty']) && $quantity > (int) $stockInfo['stockItemQty']) {
        throw new RuntimeException('Quantity exceeds available stock', 409);
    }

    $result = $cart->addItem($partId, $quantity);

    if (!$result) {
        throw new RuntimeException('Failed to add item to cart', 500);
    }

    sendResponse(getCartResponse($cart));
}

function handleUpdateQuantity(array $input, Cart $cart): void
{
    $partId = getRequiredPositiveInt($input, 'partId', 'Part ID');
    $quantity = getRequiredNonNegativeInt($input, 'quantity', 'Quantity');

    if ($quantity === 0) {
        $cart->removeItem($partId);
        sendResponse(getCartResponse($cart));
        return;
    }

    $stockInfo = $cart->checkStock($partId);

    if (empty($stockInfo['isAvailable'])) {
        throw new RuntimeException('Item is out of stock', 409);
    }

    if (isset($stockInfo['stockItemQty']) && $quantity > (int) $stockInfo['stockItemQty']) {
        throw new RuntimeException('Quantity exceeds available stock', 409);
    }

    $result = $cart->updateQuantity($partId, $quantity);

    if (!$result) {
        throw new RuntimeException('Failed to update item quantity', 500);
    }

    sendResponse(getCartResponse($cart));
}

function handleRemoveItem(array $input, Cart $cart): void
{
    $partId = getRequiredPositiveInt($input, 'partId', 'Part ID');

    $cart->removeItem($partId);
    sendResponse(getCartResponse($cart));
}

function handleBuyNow(array $input, Cart $cart): void
{
    handleAddItem($input, $cart);
}

function handleClearCart(array $input, Cart $cart): void
{
    $cart->clearCart();
    sendResponse(getCartResponse($cart));
}

function handleCheckStock(array $input, Cart $cart): void
{
    $partId = getRequiredPositiveInt($input, 'partId', 'Part ID');
    $stockInfo = $cart->checkStock($partId);

    sendResponse([
        'status' => 'success',
        'stockItemQty' => (int) ($stockInfo['stockItemQty'] ?? 0),
        'cartQuantity' => (int) ($stockInfo['cartQuantity'] ?? 0),
        'isAvailable' => !empty($stockInfo['isAvailable']),
        'message' => 'Stock checked successfully',
    ]);
}

function getCartResponse(Cart $cart): array
{
    $cartItems = [];

    foreach ($cart->getCartItems() as $item) {
        $itemDetails = $item->getItemDetails();

        if (!$itemDetails) {
            continue;
        }

        $cartItems[] = [
            'id' => (int) $item->itemID,
            'partId' => (int) $item->itemID,
            'sparePartNum' => (int) $item->itemID,
            'name' => (string) ($itemDetails['sparePartName'] ?? 'N/A'),
            'price' => (float) ($itemDetails['price'] ?? 0),
            'quantity' => (int) $item->quantity,
            'qty' => (int) $item->quantity,
            'totalPrice' => (float) $item->getTotalPrice(),
        ];
    }

    return [
        'status' => 'success',
        'message' => 'Cart retrieved successfully',
        'cartId' => $cart->getCartId(),
        'cartItems' => $cartItems,
        'cartTotal' => (float) $cart->getCartTotal(),
    ];
}

function sendResponse(array $data, int $statusCode = 200): void
{
    if ($statusCode < 100 || $statusCode > 599) {
        $statusCode = 500;
    }

    http_response_code($statusCode);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

function sendErrorResponse(string $message, int $statusCode = 400): void
{
    if ($statusCode < 400 || $statusCode > 599) {
        $statusCode = 400;
    }

    http_response_code($statusCode);
    echo json_encode([
        'status' => 'error',
        'message' => $message,
    ], JSON_THROW_ON_ERROR);
    exit;
}
