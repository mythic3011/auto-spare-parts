<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$pages = [
    'privacy-policy.php' => ['Privacy Policy', 'Your information in this demo', [
        ['Information you enter', 'The portal demonstrates account profiles, delivery addresses, orders, and sign-in sessions. Use fictional information when exploring the demo.'],
        ['Sessions and preferences', 'Session cookies support sign-in. Browser storage may remember your theme and authentication state. Sign out after using a shared computer.'],
        ['Before a live launch', 'A live service would need a published privacy contact, retention schedule, and process for access or deletion requests. This mock page does not establish those policies.'],
    ]],
    'terms-of-use.php' => ['Terms of Use', 'Explore the portal responsibly', [
        ['Demonstration only', 'This portal is a demonstration of spare-parts management. Catalog prices, stock, shipping quotes, and orders are sample information, not a commercial offer.'],
        ['Demo accounts', 'Use the supplied demonstration accounts and fictional details. Keep real passwords, payment details, and confidential business information out of the demo.'],
        ['No live purchases', 'Submitting a demo order does not create a real purchase or arrange a delivery. These sample terms are not a binding agreement.'],
    ]],
    'qa.php' => ['Q&A', 'Common questions', [
        ['What can a dealer do?', 'Browse the spare-parts catalog, inspect item details, manage a cart, review orders, and update a demo profile.'],
        ['What can staff do?', 'Explore order management, item maintenance, and reporting using the sales manager demo account.'],
        ['Why can an order fail?', 'Checkout checks current stock and shipping availability. An unavailable item or shipping service can prevent the order from completing.'],
        ['Are these real transactions?', 'No. This is a demo portal. Use fictional customer information and treat all orders as sample data.'],
    ]],
    'about.php' => ['About SLMC', 'Smart & Luxury Motor Company', [
        ['A connected spare-parts workflow', 'SLMC is the fictional company behind this demonstration of a dealer and staff spare-parts portal.'],
        ['For dealers', 'Find parts, review availability, prepare orders, and keep delivery details together in one portal.'],
        ['For staff', 'Manage sample catalog items and orders, then explore stock and sales reporting.'],
    ]],
];
$key = basename((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
if (!isset($pages[$key])) {
    http_response_code(404);
    exit;
}
[$pageTitle, $intro, $sections] = $pages[$key];
$basePath = '/';
$showHeader = false;
$showFooter = true;
$showPageLoader = false;
$e = static fn (string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
ob_start();
?>
<header class="border-b border-gray-200 dark:border-gray-800">
    <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-6">
        <a href="/" class="font-semibold tracking-tight">SLMC <span class="font-normal text-gray-500">/ Dealer portal</span></a>
        <a href="/" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Back to sign in &rarr;</a>
    </div>
</header>
<main class="mx-auto max-w-5xl px-6 py-12 sm:py-20">
    <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-400">Demo information</p>
    <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl"><?= $e($pageTitle) ?></h1>
    <p class="mt-5 text-xl text-gray-600 dark:text-gray-400"><?= $e($intro) ?></p>
    <nav aria-label="Information pages" class="my-10 flex flex-wrap gap-x-6 gap-y-3 border-y border-gray-200 py-5 text-sm dark:border-gray-800">
        <?php foreach ($pages as $href => [$title]): ?>
            <a href="/<?= $e($href) ?>" <?= $href === $key ? 'aria-current="page"' : '' ?> class="hover:underline <?= $href === $key ? 'font-semibold text-blue-600 dark:text-blue-400' : '' ?>"><?= $e($title) ?></a>
        <?php endforeach; ?>
    </nav>
    <div class="max-w-2xl space-y-8">
        <?php foreach ($sections as [$heading, $text]): ?>
            <section>
                <h2 class="text-lg font-semibold"><?= $e($heading) ?></h2>
                <p class="mt-3 leading-relaxed text-gray-600 dark:text-gray-400"><?= $e($text) ?></p>
            </section>
        <?php endforeach; ?>
    </div>
</main>
<?php
$content = (string) ob_get_clean();
require __DIR__ . '/../templates/base.php';
