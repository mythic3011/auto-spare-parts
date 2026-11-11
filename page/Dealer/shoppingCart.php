<?php
// shoppingCart.php
require_once '../../resources/php/bootstrap.php';

require_once '../../resources/php/class/Order.php';
require_once '../../resources/php/class/Cart.php';
require_once '../../resources/php/class/User.php';
require_once '../../resources/php/class/Shipping.php';

$user = new User();
$order = new Order();
$cart = new Cart();
$shipping = new Shipping();

$userLookupCandidates = array_values(array_unique(array_filter(array_map(static function ($value): string {
    return trim((string) $value);
}, [
    $_SESSION['user']['email'] ?? null,
    $_SESSION['user']['username'] ?? null,
    $_SESSION['user']['id'] ?? null,
    $_SESSION['email'] ?? null,
    $_SESSION['username'] ?? null,
]))));

$currentUserId = $userLookupCandidates[0] ?? '';
$currentUser = [];

foreach ($userLookupCandidates as $candidate) {
    try {
        $candidateUser = $user->getUser($candidate);

        if (is_array($candidateUser) && !empty($candidateUser)) {
            $currentUser = $candidateUser;
            $currentUserId = $candidate;
            break;
        }
    } catch (Throwable $e) {
        error_log('User lookup failed for shopping cart: ' . $e->getMessage());
    }
}

$cartId = (string) ($_SESSION['cartId'] ?? $_SESSION['cart_id'] ?? '');
if ($cartId === '' && method_exists($cart, 'getCartId')) {
    $cartId = (string) $cart->getCartId();
}

$shouldClearClientCartCache = false;
$placedOrderId = '';
$placedOrderItems = [];
$placedOrderSubtotal = 0.0;
$placedOrderShippingCost = 0.0;
$placedOrderTotal = 0.0;
$errorMessage = '';
$isJsonCheckout = isset($_POST['checkout']) && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$checkoutFailed = false;
$successMessage = '';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getPositiveIntFromPost(string $key): ?int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    return $value === false || $value === null ? null : $value;
}

function getCheckoutUserId(array $currentUser, string $currentUserId): string
{
    foreach (['id', 'UserID', 'userID', 'username', 'email'] as $field) {
        if (isset($currentUser[$field]) && trim((string) $currentUser[$field]) !== '') {
            return trim((string) $currentUser[$field]);
        }
    }

    return trim($currentUserId);
}

function getCartViewMode(): string
{
    $allowedModes = ['table', 'list', 'compact'];
    $requestedMode = strtolower((string) ($_GET['mode'] ?? 'table'));

    return in_array($requestedMode, $allowedModes, true) ? $requestedMode : 'table';
}

function buildModeUrl(string $mode): string
{
    $query = $_GET;
    $query['mode'] = $mode;

    return '?' . http_build_query($query);
}

function renderModeLink(string $mode, string $label, string $currentMode): void
{
    $isActive = $mode === $currentMode;
    $classes = $isActive
        ? 'bg-blue-600 text-white border-blue-600'
        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700';

    echo '<a href="' . e(buildModeUrl($mode)) . '" class="inline-flex items-center rounded-xl border px-4 py-2 text-sm font-semibold transition ' . $classes . '">' . e($label) . '</a>';
}

function getCartItemDetails($item): array
{
    return is_object($item) && method_exists($item, 'getItemDetails') ? (array) $item->getItemDetails() : [];
}

function getCartItemField($item, array $keys, $default = null)
{
    if (is_array($item)) {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item) && $item[$key] !== null && $item[$key] !== '') {
                return $item[$key];
            }
        }
    }

    if (is_object($item)) {
        foreach ($keys as $key) {
            if (isset($item->{$key}) && $item->{$key} !== '') {
                return $item->{$key};
            }
        }
    }

    $details = getCartItemDetails($item);

    foreach ($keys as $key) {
        if (array_key_exists($key, $details) && $details[$key] !== null && $details[$key] !== '') {
            return $details[$key];
        }
    }

    return $default;
}

function getCartItemName($item): string
{
    return (string) getCartItemField($item, ['name', 'sparePartName', 'itemName'], 'N/A');
}

function getCartItemPrice($item): float
{
    $price = getCartItemField($item, ['price', 'sparePartOrderPrice', 'unitPrice'], 0);

    return is_numeric($price) ? (float) $price : 0.0;
}

function getCartItemQuantity($item): int
{
    $quantity = getCartItemField($item, ['quantity', 'qty'], 0);

    return is_numeric($quantity) ? max(0, (int) $quantity) : 0;
}

function getCartItemTotal($item): float
{
    $total = getCartItemField($item, ['totalPrice', 'total'], null);

    if (is_numeric($total)) {
        return (float) $total;
    }

    return getCartItemPrice($item) * getCartItemQuantity($item);
}

function getCartItemId($item): string
{
    return (string) getCartItemField($item, ['itemID', 'id', 'partId', 'sparePartNum'], '');
}

function renderRemoveItemForm($item, string $buttonClass = 'rounded-lg bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/40'): void
{
    echo '<button type="button" class="' . e($buttonClass) . '" data-cart-action="remove" data-cart-item-id="' . e(getCartItemId($item)) . '">Remove</button>';
}

function renderQuantityControls($item): void
{
    $itemId = getCartItemId($item);
    $quantity = getCartItemQuantity($item);

    echo '<div class="flex items-center gap-2">';
    echo '<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-lg font-semibold text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700" data-cart-action="decrement" data-cart-item-id="' . e($itemId) . '" aria-label="Decrease quantity">−</button>';
    echo '<input type="number" min="1" step="1" value="' . e($quantity) . '" class="w-20 rounded-lg border border-gray-300 bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-900 dark:text-white" data-cart-action="quantity" data-cart-item-id="' . e($itemId) . '" aria-label="Quantity">';
    echo '<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-lg font-semibold text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700" data-cart-action="increment" data-cart-item-id="' . e($itemId) . '" aria-label="Increase quantity">+</button>';
    echo '</div>';
}

function renderCartItemListCard($item, bool $compact = false): void
{
    $name = getCartItemName($item);
    $id = getCartItemId($item);
    $price = getCartItemPrice($item);
    $quantity = getCartItemQuantity($item);
    $total = getCartItemTotal($item);
    $spacingClass = $compact ? 'p-4' : 'p-5';
    $titleClass = $compact ? 'text-sm' : 'text-lg';

    echo '<article class="rounded-3xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700 ' . $spacingClass . '">';
    echo '<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">';
    echo '<div class="min-w-0">';
    echo '<p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Cart item</p>';
    echo '<h3 class="mt-1 font-bold text-gray-950 dark:text-white truncate ' . $titleClass . '">' . e($name) . '</h3>';
    echo '<p class="mt-1 text-sm text-gray-600 dark:text-gray-300">No. ' . e($id) . ' · $' . number_format($price, 2) . ' × ' . e($quantity) . '</p>';
    echo '</div>';
    echo '<div class="flex shrink-0 items-center justify-between gap-4 sm:justify-end">';
    echo '<div class="flex flex-col items-end gap-3">';
    echo '<div class="text-right">';
    echo '<p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Item total</p>';
    echo '<p class="text-lg font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">$' . number_format($total, 2) . '</p>';
    echo '</div>';
    renderQuantityControls($item);
    echo '</div>';
    renderRemoveItemForm($item);
    echo '</div>';
    echo '</div>';
    echo '</article>';
}

function renderOrderConfirmation(string $placedOrderId, array $placedOrderItems, float $placedOrderSubtotal, float $placedOrderShippingCost, float $placedOrderTotal): void
{
    if ($placedOrderId === '') {
        return;
    }

    echo '<section class="mb-6 rounded-3xl border border-green-200 bg-white p-6 shadow-sm ring-1 ring-green-100 dark:border-green-900/60 dark:bg-gray-800 dark:ring-green-900/30">';
    echo '<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">';
    echo '<div>';
    echo '<p class="text-sm font-semibold uppercase tracking-wide text-green-600 dark:text-green-400">Order confirmed</p>';
    echo '<h2 class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">Order #' . e($placedOrderId) . '</h2>';
    echo '<p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Your order has been placed successfully. This confirmation shows the final checked-out items and payment summary.</p>';
    echo '</div>';
    echo '<div class="flex flex-wrap gap-2">';
    echo '<a href="viewOrderRecord.html" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">View order record</a>';
    echo '<a href="viewSparePart.php" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Continue shopping</a>';
    echo '</div>';
    echo '</div>';

    echo '<div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">';
    echo '<div class="overflow-x-auto">';
    echo '<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">';
    echo '<thead class="bg-gray-50 dark:bg-gray-900/60">';
    echo '<tr>';
    echo '<th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Item</th>';
    echo '<th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Price</th>';
    echo '<th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Qty</th>';
    echo '<th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-800">';

    foreach ($placedOrderItems as $item) {
        echo '<tr class="transition hover:bg-gray-50 dark:hover:bg-gray-900/60">';
        echo '<td class="px-5 py-4 text-sm font-semibold text-gray-950 dark:text-white">' . e(getCartItemName($item)) . '</td>';
        echo '<td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">$' . number_format(getCartItemPrice($item), 2) . '</td>';
        echo '<td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">' . e(getCartItemQuantity($item)) . '</td>';
        echo '<td class="px-5 py-4 text-sm font-bold text-blue-600 dark:text-blue-400">$' . number_format(getCartItemTotal($item), 2) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';

    echo '<div class="mt-6 grid gap-4 md:grid-cols-3">';
    echo '<div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">';
    echo '<p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Subtotal</p>';
    echo '<p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">$' . number_format($placedOrderSubtotal, 2) . '</p>';
    echo '</div>';
    echo '<div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">';
    echo '<p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Shipping</p>';
    echo '<p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">$' . number_format($placedOrderShippingCost, 2) . '</p>';
    echo '</div>';
    echo '<div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/60 dark:bg-blue-900/20">';
    echo '<p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-300">Total</p>';
    echo '<p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">$' . number_format($placedOrderTotal, 2) . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</section>';
}

function getSessionCartFallbackItems(): array
{
    foreach (['cartItems', 'cart_items'] as $key) {
        if (isset($_SESSION[$key]) && is_array($_SESSION[$key])) {
            return $_SESSION[$key];
        }
    }

    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        if (isset($_SESSION['cart']['cartItems']) && is_array($_SESSION['cart']['cartItems'])) {
            return $_SESSION['cart']['cartItems'];
        }

        if (array_is_list($_SESSION['cart'])) {
            return $_SESSION['cart'];
        }
    }

    return [];
}

// Handle form submission for placing an order or removing an item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_item_id'])) {
        $removeItemId = getPositiveIntFromPost('remove_item_id');

        if ($removeItemId === null) {
            $errorMessage = 'Invalid cart item.';
        } else {
            try {
                $cart->removeItem($removeItemId);
                $successMessage = 'Item removed successfully!';
            } catch (Exception $e) {
                error_log('Failed to remove cart item: ' . $e->getMessage());
                $errorMessage = 'Failed to remove item.';
            }
        }
    } elseif (isset($_POST['checkout'])) {
        $checkoutCartItems = $cart->getCartItems();
        $checkoutUserId = getCheckoutUserId($currentUser, $currentUserId);
        $checkoutCartId = trim((string) ($_POST['cart_id'] ?? $cartId));

        if ($checkoutCartId === '' && method_exists($cart, 'getCartId')) {
            $checkoutCartId = (string) $cart->getCartId();
        }

        if (empty($checkoutCartItems)) {
            $errorMessage = 'Your cart is empty.';
        } elseif ($checkoutUserId === '') {
            $errorMessage = 'Please log in before checkout.';
        } else {
            try {
                $placedOrderItems = $checkoutCartItems;
                $placedOrderSubtotal = calculateCartSubtotal($placedOrderItems);
                $placedOrderShippingCost = calculateShippingCost($placedOrderItems);
                $placedOrderTotal = $placedOrderSubtotal + $placedOrderShippingCost;

                $orderData = [
                    'cart' => $checkoutCartItems,
                    'cartId' => $checkoutCartId,
                    'userId' => $checkoutUserId,
                ];
                $placedOrderId = (string) $order->placeOrder($orderData, $currentUser);
                $cart->clearCart();
                $_SESSION['cartItems'] = [];
                $_SESSION['cart_items'] = [];
                $_SESSION['cart'] = [];
                unset($_SESSION['cartId'], $_SESSION['cart_id']);
                $shouldClearClientCartCache = true;
                // POST/redirect/GET prevents a refresh from submitting the order again.
                $_SESSION['checkout_confirmation'] = [
                    'order_id' => $placedOrderId,
                    'expires_at' => time() + 300,
                ];
                session_write_close();
                if ($isJsonCheckout) {
                    require_once __DIR__ . '/../../resources/php/class/HttpJson.php';
                    HttpJson::send(['redirect' => 'checkout.php']);
                }
                header('Location: checkout.php', true, 303);
                exit;
            } catch (Exception $e) {
                error_log('Failed to place order: ' . $e->getMessage());
                $checkoutFailed = true;
                $errorMessage = 'Failed to place order.';
            }
        }
    }
}

if ($isJsonCheckout && $errorMessage !== '') {
    require_once __DIR__ . '/../../resources/php/class/HttpJson.php';
    HttpJson::send(['error' => $errorMessage], $checkoutFailed ? 503 : 400);
}

$cartItems = $shouldClearClientCartCache ? [] : $cart->getCartItems();

if (!$shouldClearClientCartCache && empty($cartItems)) {
    $cartItems = getSessionCartFallbackItems();
}

function getCartItemWeight($item): float
{
    $weight = getCartItemField($item, ['weight', 'unitWeight', 'sparePartWeight'], 0);

    return is_numeric($weight) ? max(0.0, (float) $weight) : 0.0;
}

function calculateLocalShippingFallback(float $totalWeight, int $totalQuantity): float
{
    if ($totalQuantity <= 0) {
        return 0.0;
    }

    if ($totalWeight > 0) {
        return 300.0 + max(0, (int) ceil($totalWeight - 1)) * 50.0;
    }

    return 300.0 + max(0, $totalQuantity - 1) * 60.0;
}

function calculateCartSubtotal(array $cartItems): float
{
    return array_reduce($cartItems, static function ($sum, $item) {
        return $sum + getCartItemTotal($item);
    }, 0.0);
}

function calculateShippingCost($cart): float
{
    global $shipping;

    $totalWeight = 0.0;
    $totalQuantity = 0;

    foreach ($cart as $item) {
        $quantity = getCartItemQuantity($item);
        $totalWeight += getCartItemWeight($item) * $quantity;
        $totalQuantity += $quantity;
    }

    if ($totalQuantity <= 0) {
        return 0.0;
    }

    $fallbackCost = calculateLocalShippingFallback($totalWeight, $totalQuantity);

    try {
        $shipCostWeight = $totalWeight > 0
            ? $shipping->calculateShippingCost('weight', $totalWeight, $fallbackCost)
            : $fallbackCost;

        $shipCostQuantity = $shipping->calculateShippingCost('quantity', $totalQuantity, $fallbackCost);

        return max($shipCostWeight, $shipCostQuantity, $fallbackCost);
    } catch (Throwable $e) {
        error_log('Shipping cost fallback used: ' . $e->getMessage());
        return $fallbackCost;
    }
}

$shippingCost = calculateShippingCost($cartItems);
$subtotal = calculateCartSubtotal($cartItems);

$totalAmount = $subtotal + $shippingCost;
$cartViewMode = getCartViewMode();
?>

<!doctype html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shopping cart | SLMC Portal</title>
    <meta name="description" content="Review your selected parts and place a demo order.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="application-name" content="SLMC Portal">
    <meta name="theme-color" content="#f8fafc" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#020617" media="(prefers-color-scheme: dark)">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SLMC Portal">
    <meta property="og:title" content="Shopping cart | SLMC Portal">
    <meta property="og:description" content="Review your selected parts and place a demo order.">
    <meta property="og:image" content="/resources/image/SLMC_Icon.png">
    <link rel="icon" href="/resources/image/SLMC_Icon.png" type="image/png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="/resources/image/SLMC_Icon_DM.png" type="image/png" media="(prefers-color-scheme: dark)">
    <link rel="apple-touch-icon" href="/resources/image/SLMC_Icon.png">
    <script src="/resources/js/theme.global.js"></script>





    <link rel="stylesheet" href="/resources/css/portal.css">
    <script type="module" src="../../resources/js/script.js?v=20260524-safe1"></script>

    <style>
        [hidden] {
            display: none !important;
        }
    </style>
</head>

<body class="portal-page min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-white">
<nav class="sticky top-0 z-50 border-b border-gray-200/80 bg-white/90 backdrop-blur dark:border-gray-700/80 dark:bg-gray-900/90">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <a href="viewSparePart.php" class="flex items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900" aria-label="SLMC dealer home">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                    <img alt="SLMC Logo" class="h-7 w-7 object-contain dark:hidden" src="../../resources/image/SLMC_LOGO.png"/>
                    <img alt="SLMC Logo" class="hidden h-7 w-7 object-contain dark:block" src="../../resources/image/SLMC_LOGO_DM.png"/>
                </span>
                <span class="flex flex-col leading-tight">
                    <span class="text-base font-bold text-gray-900 dark:text-white">SLMC</span>
                    <span class="hidden text-xs text-gray-500 dark:text-gray-400 sm:block">Dealer Portal</span>
                </span>
            </a>

            <div class="hidden items-center gap-1 md:flex" id="desktop-navigation">
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-blue-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-blue-400" href="viewOrderRecord.html">Orders</a>
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-blue-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-blue-400" href="viewSparePart.php">Spare Parts</a>
                <a class="rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 ring-1 ring-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800" href="shoppingCart.php" aria-current="page">Cart</a>
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-blue-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-blue-400" href="profile.html">Profile</a>
            </div>

            <div class="flex items-center gap-2">
                <button id="darkModeToggle" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white" aria-label="Toggle dark mode">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                </button>

                <a href="../../logout.php" class="hidden rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-red-900/20 dark:hover:text-red-300 sm:inline-flex">Log out</a>

                <button id="mobile-menu-button" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white md:hidden" aria-controls="mobile-navigation" aria-expanded="false" aria-label="Open main menu">
                    <svg aria-hidden="true" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 17 14" fill="none">
                        <path d="M1 1h15M1 7h15M1 13h15" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="hidden border-t border-gray-200 py-3 dark:border-gray-700 md:hidden" id="mobile-navigation">
            <div class="grid gap-1">
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800" href="viewOrderRecord.html">Orders</a>
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800" href="viewSparePart.php">Spare Parts</a>
                <a class="rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300" href="shoppingCart.php" aria-current="page">Cart</a>
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800" href="profile.html">Profile</a>
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/20" href="../../logout.php">Log out</a>
            </div>
        </div>
    </div>
</nav>
<main class="mx-auto flex w-full max-w-7xl flex-col px-4 py-8 sm:px-6 lg:px-8">
    <?php if ($placedOrderId === ''): ?>
        <section class="mb-6 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Dealer checkout</p>
                    <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-gray-950 dark:text-white">Shopping Cart</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Review spare parts, shipping cost, and checkout details before placing an order.</p>
                </div>
                <div class="flex flex-wrap gap-2" aria-label="Cart view modes">
                    <?php renderModeLink('table', 'Table', $cartViewMode); ?>
                    <?php renderModeLink('list', 'List', $cartViewMode); ?>
                    <?php renderModeLink('compact', 'Compact', $cartViewMode); ?>
                </div>
            </div>
        </section>

        <div id="clientCartSyncState" hidden class="mb-4 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm font-medium text-blue-800 shadow-sm dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
            Syncing cart with server stock validation...
        </div>

        <div class="mb-6 flex items-center justify-between rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Items in cart</p>
            <span id="cartItemCount" class="rounded-full bg-blue-50 px-3 py-1 text-sm font-bold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"><?= e(count($cartItems)) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
        <div class="mb-4 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
            <?= e($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php renderOrderConfirmation($placedOrderId, $placedOrderItems, $placedOrderSubtotal, $placedOrderShippingCost, $placedOrderTotal); ?>

    <?php if ($placedOrderId === ''): ?>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Cart Items -->
        <div class="lg:col-span-2">
            <div id="serverCartContent" hidden></div>

            <div id="cartEmptyState" class="hidden rounded-3xl border border-gray-200 bg-white p-10 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800" hidden>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Your cart is empty</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Add spare parts before checkout.</p>
                <a href="viewSparePart.php" class="mt-5 inline-flex rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Browse spare parts</a>
            </div>

            <div id="clientCartTableContainer" class="hidden overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" hidden>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/60">
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Item</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Price</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Quantity</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                        </thead>
                        <tbody id="clientCartBody" class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-800"></tbody>
                    </table>
                </div>
            </div>

            <div id="clientCartListContainer" class="hidden space-y-4" hidden></div>
            <div id="clientCartCompactContainer" class="hidden space-y-3" hidden></div>
        </div>

        <!-- Order summary uses the same surface and controls as sign-in. -->
        <aside id="orderSummaryContainer" class="lg:col-span-1" aria-labelledby="orderSummaryTitle">
            <div class="sticky top-24 overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-lg shadow-blue-950/5 dark:border-gray-700 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-6 dark:border-gray-700">
                    <p class="text-xs font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-400">Ready when you are</p>
                    <h2 id="orderSummaryTitle" class="mt-2 text-2xl font-semibold tracking-tight">Order Summary</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><span id="summaryItemCount"><?= array_sum(array_map('getCartItemQuantity', $cartItems)) ?></span> items in your order</p>
                </div>
                <div class="px-6 py-6">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Deliver to</h3>
                        <a href="profileSetting.html" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">Edit details</a>
                    </div>
                    <p class="mt-4 font-semibold"><?= e($currentUser['contactName'] ?? $currentUser['FullName'] ?? 'No contact name') ?></p>
                    <address class="mt-2 text-sm not-italic leading-relaxed text-gray-600 dark:text-gray-400"><?= e($currentUser['deliveryAddress'] ?? $currentUser['addressLine1'] ?? 'Add a delivery address in your profile.') ?></address>
                    <form method="POST" id="checkoutForm" class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
                        <input type="hidden" name="checkout" value="1">
                        <input type="hidden" name="cart_id" value="<?= e($cartId) ?>">
                        <dl class="space-y-4 text-sm">
                            <div class="flex items-center justify-between gap-4 text-gray-600 dark:text-gray-400">
                                <dt>Parts subtotal</dt>
                                <dd id="cartSubtotal" class="font-medium text-gray-900 dark:text-white">$<?= number_format($subtotal, 2) ?></dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 text-gray-600 dark:text-gray-400">
                                <dt>Shipping</dt>
                                <dd id="cartShipping" class="text-right">Confirmed at checkout</dd>
                            </div>
                            <div class="flex items-end justify-between gap-4 border-t border-gray-200 pt-5 dark:border-gray-700">
                                <dt><span class="block font-semibold">Total before shipping</span><span class="mt-1 block text-xs text-gray-500">HKD</span></dt>
                                <dd id="cartTotal" class="text-3xl font-semibold tracking-tight text-blue-600 dark:text-blue-400">$<?= number_format($subtotal, 2) ?></dd>
                            </div>
                        </dl>
                        <p id="checkoutFeedback" role="alert" hidden class="mt-4 text-sm text-red-600 dark:text-red-400"></p>
                        <button class="mt-6 flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-4 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50" type="submit" id="checkoutButton" disabled>Checkout &rarr;</button>
                        <p class="mt-4 text-center text-xs leading-relaxed text-gray-500 dark:text-gray-400">Stock and shipping are checked before your order is confirmed.</p>
                    </form>
                </div>
            </div>
        </aside>
    </div>
    <?php endif; ?>
</main>
<div id="checkoutProgress" hidden class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 p-6" role="status" aria-live="polite" aria-label="Placing your order">
    <div class="w-full max-w-sm rounded-2xl bg-white p-8 text-center shadow-xl dark:bg-gray-900">
        <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-blue-100 border-t-blue-600 motion-reduce:animate-none" aria-hidden="true"></div>
        <h2 class="mt-5 text-xl font-semibold">Placing your order</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Checking stock and shipping. Please keep this page open.</p>
    </div>
</div>
<script src="/resources/js/checkout-progress.js" defer></script>
<footer class="mt-10 border-t border-gray-200 py-6 text-center dark:border-gray-800">
    <p class="text-sm text-gray-600 dark:text-gray-400">© 2024 Smart & Luxury Motor Company. All rights reserved.</p>
</footer>
<script>
    window.__SERVER_CART_COUNT__ = <?= $placedOrderId !== '' ? 0 : (int) count($cartItems) ?>;
    window.__SERVER_CART_TOTAL__ = <?= json_encode($placedOrderId !== '' ? 0.0 : $totalAmount, JSON_THROW_ON_ERROR) ?>;
</script>
<?php if ($shouldClearClientCartCache): ?>
<script>
    localStorage.removeItem('cart');
    localStorage.removeItem('cartItems');
    localStorage.removeItem('shoppingCart');
    localStorage.removeItem('cartId');
    window.__ORDER_PLACED__ = true;
</script>
<?php endif; ?>
<script type="module" src="../../resources/js/module/cart.js?v=20260524-server-cart7"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuButton = document.getElementById('mobile-menu-button');
        const mobileNavigation = document.getElementById('mobile-navigation');

        if (!menuButton || !mobileNavigation) {
            return;
        }

        menuButton.addEventListener('click', () => {
            const isExpanded = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', String(!isExpanded));
            mobileNavigation.classList.toggle('hidden', isExpanded);
        });
    });
</script>
</body>

</html>
