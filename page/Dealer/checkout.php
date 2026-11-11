<?php

declare(strict_types=1);

require_once __DIR__ . '/../../resources/php/bootstrap.php';
$confirmation = $_SESSION['checkout_confirmation'] ?? null;
unset($_SESSION['checkout_confirmation']);
if (!is_array($confirmation)
    || !ctype_digit((string) ($confirmation['order_id'] ?? ''))
    || (int) ($confirmation['expires_at'] ?? 0) < time()) {
    header('Location: shoppingCart.php', true, 303);
    exit;
}
$orderId = (string) $confirmation['order_id'];
$destination = 'OrderDetails.html?id=' . rawurlencode($orderId);
$pageTitle = 'Order placed';
$basePath = '/';
$showPageLoader = false;
$containerClass = 'mx-auto flex min-h-screen max-w-xl items-center px-6 py-12';
ob_start();
?>
<section class="w-full text-center" aria-labelledby="checkoutTitle">
    <p class="text-sm font-semibold uppercase tracking-widest text-green-600 dark:text-green-400">Order confirmed</p>
    <h1 id="checkoutTitle" class="mt-4 text-3xl font-semibold">Your order has been placed</h1>
    <p class="mt-4 text-gray-600 dark:text-gray-400">Opening the details for order #<?= htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') ?>.</p>
    <div class="mx-auto mt-8 h-10 w-10 animate-spin rounded-full border-4 border-blue-100 border-t-blue-600 motion-reduce:animate-none" role="status" aria-label="Opening order details"></div>
    <a id="checkoutDestination" href="<?= htmlspecialchars($destination, ENT_QUOTES, 'UTF-8') ?>" class="mt-8 inline-block rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700">View order details &rarr;</a>
    <noscript><p class="mt-4 text-sm">Select View order details to continue.</p></noscript>
</section>
<?php
$content = (string) ob_get_clean();
$inlineScript = '<script src="/resources/js/checkout-redirect.js" defer></script>';
require __DIR__ . '/../../resources/templates/base.php';
