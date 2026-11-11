<?php
require_once '../../resources/php/bootstrap.php';

require_once '../../resources/php/class/SparePart.php';

$sparePart = new SparePart();

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getSparePartValue(array $part, string $key, string $default = ''): string
{
    $fieldMap = [
        'id' => ['id', 'sparePartNum'],
        'name' => ['name', 'sparePartName'],
        'category' => ['category', 'sparePartCategory'],
        'imageUrl' => ['imageUrl', 'sparePartImage'],
    ];

    foreach ($fieldMap[$key] ?? [$key] as $field) {
        if (array_key_exists($field, $part) && $part[$field] !== null && $part[$field] !== '') {
            return (string) $part[$field];
        }
    }

    return $default;
}

function getCartPartId(array $part): string
{
    foreach (['id', 'partId', 'sparePartId', 'sparePartNum'] as $field) {
        if (!array_key_exists($field, $part)) {
            continue;
        }

        $value = trim((string) $part[$field]);

        if ($value !== '' && ctype_digit($value) && (int) $value > 0) {
            return $value;
        }
    }

    return '';
}

function getSparePartPrice(array $part): float
{
    return is_numeric($part['price'] ?? null) ? (float) $part['price'] : 0.0;
}

function getSparePartStock(array $part): int
{
    $value = $part['stockItemQty'] ?? $part['stock'] ?? $part['quantity'] ?? 0;

    return is_numeric($value) ? max(0, (int) $value) : 0;
}

function getStockBadgeClasses(int $stock): string
{
    if ($stock <= 0) {
        return 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-900/20 dark:text-red-300 dark:ring-red-800';
    }

    if ($stock <= 5) {
        return 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-800';
    }

    return 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-900/20 dark:text-green-300 dark:ring-green-800';
}

function getStockLabel(int $stock): string
{
    if ($stock <= 0) {
        return 'Out of stock';
    }

    if ($stock <= 5) {
        return 'Low stock · ' . $stock . ' left';
    }

    return 'In stock · ' . $stock . ' available';
}

function getSparePartDescription(array $part): string
{
    $description = trim(getSparePartValue($part, 'sparePartDescription'));

    if ($description === '') {
        return 'View details for pricing, specification, and order options.';
    }

    return mb_strlen($description) > 120 ? mb_substr($description, 0, 117) . '...' : $description;
}

function getSafeImageUrl(array $part): string
{
    $imageValue = getSparePartValue($part, 'imageUrl');
    $fileName = basename(str_replace('\\', '/', $imageValue));

    if ($fileName === '') {
        return '../../resources/image/SLMC_Icon_DM.png';
    }

    return '../../resources/image/spare/' . rawurlencode($fileName);
}

function getGalleryMode(): string
{
    $allowedModes = ['grid', 'list', 'compact', 'detail'];
    $requestedMode = strtolower((string) ($_GET['mode'] ?? 'grid'));

    return in_array($requestedMode, $allowedModes, true) ? $requestedMode : 'grid';
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

    echo '<a href="' . e(buildModeUrl($mode)) . '" class="inline-flex items-center px-3 py-2 text-sm font-medium border rounded-lg transition ' . $classes . '">' . e($label) . '</a>';
}

function renderSparePartCard(array $part, string $mode = 'grid'): void
{
    $id = getSparePartValue($part, 'id');
    $cartPartId = getCartPartId($part);
    $name = getSparePartValue($part, 'name', 'Unnamed spare part');
    $category = getSparePartValue($part, 'category', 'Uncategorised');
    $categoryLabel = is_numeric($category) ? chr(64 + (int) $category) : $category;
    $price = getSparePartPrice($part);
    $stock = getSparePartStock($part);
    $stockLabel = getStockLabel($stock);
    $stockBadgeClasses = getStockBadgeClasses($stock);
    $isOutOfStock = $stock <= 0;
    $canAddToCart = !$isOutOfStock && $cartPartId !== '';
    $imageUrl = getSafeImageUrl($part);
    $description = getSparePartDescription($part);
    $detailUrl = 'sparePart-template.html?id=' . rawurlencode($id);
    $actionButtonClasses = !$canAddToCart
        ? 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600'
        : 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm shadow-blue-600/20';
    $quickActionLabel = $isOutOfStock ? 'Unavailable' : ($cartPartId === '' ? 'Invalid part' : 'Add to cart');

    if ($mode === 'list') {
        echo '<article class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex flex-col sm:flex-row sm:items-center gap-5 hover:shadow-lg hover:-translate-y-0.5 hover:border-blue-300 dark:hover:border-blue-700 transition">';
        echo '<a href="' . e($detailUrl) . '" class="shrink-0 overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-900">';
        echo '<img class="w-full sm:w-32 h-32 object-contain bg-white dark:bg-gray-900 group-hover:scale-105 transition" src="' . e($imageUrl) . '" alt="' . e($name) . '">';
        echo '</a>';
        echo '<div class="flex-1 min-w-0">';
        echo '<div class="flex flex-wrap items-center gap-2 mb-2">';
        echo '<span class="inline-flex rounded-full bg-blue-50 dark:bg-blue-900/30 px-2.5 py-1 text-xs font-medium text-blue-700 dark:text-blue-300">Category ' . e($categoryLabel) . '</span>';
        echo '<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ' . e($stockBadgeClasses) . '">' . e($stockLabel) . '</span>';
        echo '<span class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300">No. ' . e($id) . '</span>';
        echo '</div>';
        echo '<h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">' . e($name) . '</h3>';
        echo '<p class="mt-1 text-sm text-gray-600 dark:text-gray-300 line-clamp-2">' . e($description) . '</p>';
        echo '</div>';
        echo '<div class="sm:text-right shrink-0">';
        echo '<p class="text-xl font-bold text-blue-600 dark:text-blue-400">$' . number_format($price, 2) . '</p>';
        echo '<a href="' . e($detailUrl) . '" class="mt-3 inline-flex justify-center rounded-lg px-4 py-2 text-sm font-semibold transition ' . ($isOutOfStock ? 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' : 'bg-blue-600 text-white hover:bg-blue-700') . '">' . ($isOutOfStock ? 'View item' : 'View Details') . '</a>';
        echo '</div>';
        echo '</article>';
        return;
    }

    if ($mode === 'compact') {
        echo '<article class="group bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4 hover:shadow-lg hover:-translate-y-0.5 hover:border-blue-300 dark:hover:border-blue-700 transition">';
        echo '<a href="' . e($detailUrl) . '" class="shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-900">';
        echo '<img class="w-20 h-20 object-contain bg-white dark:bg-gray-900 group-hover:scale-105 transition" src="' . e($imageUrl) . '" alt="' . e($name) . '">';
        echo '</a>';
        echo '<div class="flex-1 min-w-0">';
        echo '<h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">' . e($name) . '</h3>';
        echo '<p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate">No. ' . e($id) . ' · Category ' . e($categoryLabel) . '</p>';
        echo '<span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ' . e($stockBadgeClasses) . '">' . e($stockLabel) . '</span>';
        echo '<p class="mt-1 text-sm font-semibold text-blue-600 dark:text-blue-400">$' . number_format($price, 2) . '</p>';
        echo '</div>';
        echo '<a href="' . e($detailUrl) . '" class="rounded-lg px-3 py-2 text-sm font-semibold whitespace-nowrap transition ' . ($isOutOfStock ? 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' : 'bg-blue-600 text-white hover:bg-blue-700') . '">Details</a>';
        echo '</article>';
        return;
    }

    if ($mode === 'detail') {
        echo '<article class="group bg-white dark:bg-gray-800 rounded-2xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition">';
        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-0">';
        echo '<a href="' . e($detailUrl) . '" class="bg-gray-100 dark:bg-gray-900 p-4">';
        echo '<img class="w-full h-80 object-contain rounded-xl bg-white dark:bg-gray-900 group-hover:scale-[1.02] transition" src="' . e($imageUrl) . '" alt="' . e($name) . '">';
        echo '</a>';
        echo '<div class="p-6 flex flex-col justify-between gap-6">';
        echo '<div>';
        echo '<div class="mb-3 flex flex-wrap gap-2">';
        echo '<span class="rounded-full bg-blue-50 dark:bg-blue-900/30 px-3 py-1 text-xs font-semibold text-blue-700 dark:text-blue-300">No. ' . e($id) . '</span>';
        echo '<span class="rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-600 dark:text-gray-300">Category ' . e($categoryLabel) . '</span>';
        echo '<span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 ' . e($stockBadgeClasses) . '">' . e($stockLabel) . '</span>';
        echo '</div>';
        echo '<h3 class="text-2xl font-bold text-gray-900 dark:text-white">' . e($name) . '</h3>';
        echo '<p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">' . e($description) . '</p>';
        echo '<p class="mt-5 text-3xl font-bold text-blue-600 dark:text-blue-400">$' . number_format($price, 2) . '</p>';
        echo '</div>';
        echo '<a href="' . e($detailUrl) . '" class="inline-flex justify-center rounded-lg px-4 py-3 text-sm font-semibold transition ' . ($isOutOfStock ? 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600' : 'bg-blue-600 text-white hover:bg-blue-700') . '">' . ($isOutOfStock ? 'View unavailable item' : 'View Full Details') . '</a>';
        echo '</div>';
        echo '</div>';
        echo '</article>';
        return;
    }

    echo '<article class="group relative overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-blue-300 hover:shadow-xl dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700">';
    echo '<div class="relative bg-gradient-to-br from-gray-50 to-gray-100 p-4 dark:from-gray-900 dark:to-gray-800">';
    echo '<div class="absolute left-4 top-4 z-20 rounded-full px-3 py-1 text-xs font-semibold ring-1 backdrop-blur-md ' . e($stockBadgeClasses) . '">' . e($stockLabel) . '</div>';
    echo '<a href="' . e($detailUrl) . '" class="block overflow-hidden rounded-2xl border border-gray-200 bg-white/90 p-3 dark:border-gray-700 dark:bg-gray-950/40">';
    echo '<img class="h-56 w-full object-contain transition duration-300 group-hover:scale-105" src="' . e($imageUrl) . '" alt="' . e($name) . '" loading="lazy">';
    echo '</a>';
    echo '<div class="pointer-events-none absolute inset-4 z-10 flex items-end justify-center rounded-2xl bg-gradient-to-t from-gray-950/75 via-gray-950/20 to-transparent opacity-0 transition duration-200 group-hover:opacity-100">';
    echo '<div class="pointer-events-auto mb-4 flex flex-wrap justify-center gap-2 px-3">';
    echo '<a href="' . e($detailUrl) . '" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm transition hover:bg-gray-100">Quick view</a>';
    echo '<button type="button" class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold transition ' . e($actionButtonClasses) . '" data-cart-action="add" data-part-id="' . e($cartPartId) . '" data-cart-label="' . e($quickActionLabel) . '" data-display-part-id="' . e($id) . '" ' . (!$canAddToCart ? 'disabled aria-disabled="true"' : '') . '>' . e($quickActionLabel) . '</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="p-5">';
    echo '<div class="mb-3 flex flex-wrap gap-2">';
    echo '<span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">Category ' . e($categoryLabel) . '</span>';
    echo '<span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">No. ' . e($id) . '</span>';
    echo '</div>';
    echo '<h3 class="text-lg font-semibold leading-snug text-gray-950 line-clamp-2 min-h-[3.5rem] dark:text-white">' . e($name) . '</h3>';
    echo '<p class="mt-2 text-sm leading-6 text-gray-600 line-clamp-2 min-h-[3rem] dark:text-gray-300">' . e($description) . '</p>';
    echo '<div class="mt-5 flex items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">';
    echo '<div>';
    echo '<p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Unit price</p>';
    echo '<p class="text-2xl font-bold text-blue-600 dark:text-blue-400">$' . number_format($price, 2) . '</p>';
    echo '</div>';
    echo '<a href="' . e($detailUrl) . '" class="inline-flex justify-center rounded-xl px-4 py-2 text-sm font-semibold transition ' . e($actionButtonClasses) . '">Details</a>';
    echo '</div>';
    echo '</div>';
    echo '</article>';
}

// Fetch spare parts data from database
$spareParts = $sparePart->getAllSpareParts();
$galleryMode = getGalleryMode();
?>

<!doctype html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Spare parts | SLMC Portal</title>
    <meta name="description" content="Browse the SLMC spare-parts catalog and current availability.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="application-name" content="SLMC Portal">
    <meta name="theme-color" content="#f8fafc" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#020617" media="(prefers-color-scheme: dark)">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SLMC Portal">
    <meta property="og:title" content="Spare parts | SLMC Portal">
    <meta property="og:description" content="Browse the SLMC spare-parts catalog and current availability.">
    <meta property="og:image" content="/resources/image/SLMC_Icon.png">
    <link rel="icon" href="/resources/image/SLMC_Icon.png" type="image/png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="/resources/image/SLMC_Icon_DM.png" type="image/png" media="(prefers-color-scheme: dark)">
    <link rel="apple-touch-icon" href="/resources/image/SLMC_Icon.png">
    <script src="/resources/js/theme.global.js"></script>

    
    
    
    
    <link rel="stylesheet" href="/resources/css/portal.css">
    <script type="module" src="../../resources/js/script.js?v=20260524-safe1"></script>
    <script src="../../resources/js/module/config/endpoints.global.js"></script>
    <style>
        [hidden] {
            display: none !important;
        }
    </style>
    
</head>

<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
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
                <a class="rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 ring-1 ring-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800" href="viewSparePart.php" aria-current="page">Spare Parts</a>
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-blue-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-blue-400" href="shoppingCart.php">Cart</a>
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-blue-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-blue-400" href="profile.html">Profile</a>
            </div>

            <div class="flex items-center gap-2">
                <button id="darkModeToggle"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                        aria-label="Toggle dark mode">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                </button>

                <a href="../../logout.php"
                   class="hidden rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-red-900/20 dark:hover:text-red-300 sm:inline-flex">
                    Log out
                </a>

                <button id="mobile-menu-button"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 shadow-sm transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white md:hidden"
                        aria-controls="mobile-navigation"
                        aria-expanded="false"
                        aria-label="Open main menu">
                    <svg aria-hidden="true" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 17 14" fill="none">
                        <path d="M1 1h15M1 7h15M1 13h15" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="hidden border-t border-gray-200 py-3 dark:border-gray-700 md:hidden" id="mobile-navigation">
            <div class="grid gap-1">
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800" href="viewOrderRecord.html">Orders</a>
                <a class="rounded-lg bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300" href="viewSparePart.php" aria-current="page">Spare Parts</a>
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800" href="shoppingCart.php">Cart</a>
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800" href="profile.html">Profile</a>
                <a class="rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/20" href="../../logout.php">Log out</a>
            </div>
        </div>
    </div>
</nav>
<main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <section class="mb-6 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Dealer catalogue</p>
                <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-gray-950 dark:text-white">Spare Parts</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Find parts faster with search, price sorting, stock indicators, and multiple gallery views.</p>
            </div>
            <div class="flex flex-wrap gap-2" aria-label="Gallery view modes">
                <?php renderModeLink('grid', 'Grid', $galleryMode); ?>
                <?php renderModeLink('list', 'List', $galleryMode); ?>
                <?php renderModeLink('compact', 'Compact', $galleryMode); ?>
                <?php renderModeLink('detail', 'Detail', $galleryMode); ?>
            </div>
        </div>
    </section>
    <div class="mb-6 rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <!-- Search Bar -->
        <div class="w-full">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="sparePartSearch">Search spare parts</label>
            <input class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   id="sparePartSearch"
                   placeholder="Search by name, category, or part number"
                   type="text">
        </div>

        <!-- Sort By Dropdown -->
        <div class="w-full">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="SparePartSorting">Sort by</label>
            <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    id="SparePartSorting">
                <option value="None">None</option>
                <option value="Price-Low-to-High">Price - Low to High</option>
                <option value="Price-High-to-Low">Price - High to Low</option>
                <option value="Category">Category</option>
            </select>
        </div>

        <!-- Specify Category Dropdown -->
        <div class="w-full" id="CategoryFilterContainer" style="display: none;">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="CategoryFilter">Category</label>
            <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    id="CategoryFilter">
                <option value="All">All Categories</option>

            </select>
        </div>
    </div>
    <?php
    $listClassByMode = [
        'grid' => 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6',
        'list' => 'space-y-4',
        'compact' => 'grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3',
        'detail' => 'space-y-6',
    ];
    ?>
    <div id="SparePartList" class="<?= e($listClassByMode[$galleryMode] ?? $listClassByMode['grid']) ?>" data-gallery-mode="<?= e($galleryMode) ?>">
        <?php if (empty($spareParts)): ?>
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">No spare parts found</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Try changing the search, sorting, or gallery mode.</p>
            </div>
        <?php else: ?>
            <?php foreach ($spareParts as $part): ?>
                <?php renderSparePartCard($part, $galleryMode); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
<footer class="bg-white border-t border-gray-200 dark:bg-gray-900 dark:border-gray-700">
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-wrap items-center justify-between md:flex-no-wrap">
            <div class="flex items-center mb-6 md:mb-0">
                <span class="text-sm text-gray-500 dark:text-gray-400">Copyright &copy; 2023 Smart & Luxury Motor Company</span>
            </div>
        </div>
    </div>
</footer>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const endpointConfig = window.APP_API_ENDPOINTS || {};
        const CART_API_URL = endpointConfig.cart;
        if (!CART_API_URL) {
            throw new Error("Missing APP_API_ENDPOINTS.cart");
        }

        const saveCartCache = (payload) => {
            const cart = payload?.result && typeof payload.result === 'object' ? payload.result : payload;
            const cartItems = Array.isArray(cart?.cartItems)
                ? cart.cartItems
                : Array.isArray(cart?.items)
                    ? cart.items
                    : [];

            localStorage.setItem('cart', JSON.stringify({
                cartId: cart?.cartId || localStorage.getItem('cartId') || '',
                cartItems,
            }));
            localStorage.setItem('cartItems', JSON.stringify(cartItems));

            if (cart?.cartId) {
                localStorage.setItem('cartId', String(cart.cartId));
            }
        };

        const setButtonState = (button, state, message = '') => {
            const originalLabel = button.getAttribute('data-cart-label') || 'Add to cart';

            if (state === 'loading') {
                button.disabled = true;
                button.textContent = 'Adding...';
                button.classList.add('opacity-75', 'cursor-wait');
                return;
            }

            button.classList.remove('opacity-75', 'cursor-wait');

            if (state === 'success') {
                button.disabled = false;
                button.textContent = 'Added · View cart';
                button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                button.classList.add('bg-green-600', 'hover:bg-green-700', 'text-white');
                button.dataset.cartAction = 'view-cart';
                return;
            }

            if (state === 'error') {
                button.disabled = false;
                button.textContent = message || 'Failed';
                button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                button.classList.add('bg-red-600', 'hover:bg-red-700', 'text-white');

                window.setTimeout(() => {
                    button.textContent = originalLabel;
                    button.classList.remove('bg-red-600', 'hover:bg-red-700');
                    button.classList.add('bg-blue-600', 'hover:bg-blue-700');
                }, 1800);
            }
        };

        const addToCart = async (button) => {
            const partId = button.getAttribute('data-part-id');

            if (!partId || button.disabled) {
                setButtonState(button, 'error', 'Invalid part');
                return;
            }

            setButtonState(button, 'loading');

            try {
                const response = await fetch(`${CART_API_URL}?action=add`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        partId,
                        quantity: 1,
                    }),
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok || payload.status === 'error') {
                    throw new Error(payload.message || payload.error || `Cart API failed. HTTP ${response.status}`);
                }

                saveCartCache(payload);
                setButtonState(button, 'success');
            } catch (error) {
                console.error('Failed to add spare part to cart:', error);
                const message = String(error.message || 'Failed');
                setButtonState(button, 'error', message.length > 28 ? 'Add failed' : message);
            }
        };

        document.addEventListener('click', (event) => {
            const button = event.target instanceof Element
                ? event.target.closest('[data-cart-action]')
                : null;

            if (!button) {
                return;
            }

            const action = button.getAttribute('data-cart-action');

            if (action === 'view-cart') {
                window.location.href = 'shoppingCart.php';
                return;
            }

            if (action !== 'add') {
                return;
            }

            event.preventDefault();
            addToCart(button);
        });
    });
</script>
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
