<?php
// Footer template
$year = date('Y');

$basePath = $basePath ?? '/';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('footer_e')) {
    function footer_e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$isAuthenticated = !empty($_SESSION['user'])
    || !empty($_SESSION['UserID'])
    || !empty($_SESSION['userID'])
    || !empty($_SESSION['username'])
    || !empty($_SESSION['email']);

$userRole = (string) (
    $_SESSION['user']['role']
    ?? $_SESSION['user']['Role']
    ?? $_SESSION['role']
    ?? $_SESSION['Role']
    ?? ''
);

$isSalesManager = stripos($userRole, 'Sales Manager') !== false;

$footerLinks = $isAuthenticated
    ? ($isSalesManager
        ? [
            ['label' => 'Orders', 'href' => $basePath . 'page/Staff/order.html'],
            ['label' => 'Stock Report', 'href' => $basePath . 'page/Staff/stockReport.html'],
        ]
        : [
            ['label' => 'Spare Parts', 'href' => $basePath . 'page/Dealer/viewSparePart.php'],
            ['label' => 'Orders', 'href' => $basePath . 'page/Dealer/viewOrderRecord.html'],
            ['label' => 'Cart', 'href' => $basePath . 'page/Dealer/shoppingCart.php'],
        ])
    : [
        ['label' => 'Privacy Policy', 'href' => $basePath . 'privacy-policy.php'],
        ['label' => 'Terms of Use', 'href' => $basePath . 'terms-of-use.php'],
        ['label' => 'Q&A', 'href' => $basePath . 'qa.php'],
        ['label' => 'About SLMC', 'href' => $basePath . 'about.php'],
    ];
?>

<footer class="mt-12 border-t border-gray-200 bg-white text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
    <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <?php if (!empty($footerLinks)): ?>
                <nav aria-label="Footer navigation">
                    <ul class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                        <?php foreach ($footerLinks as $link): ?>
                            <li>
                                <a href="<?= footer_e($link['href']) ?>" class="font-medium transition hover:text-gray-950 dark:hover:text-white">
                                    <?= footer_e($link['label']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>

        <div class="mt-6 border-t border-gray-200 pt-5 text-xs dark:border-gray-800">
            <p>&copy; <?= footer_e($year) ?> Smart &amp; Luxury Motor Company. All rights reserved.</p>
        </div>
    </div>
</footer>
