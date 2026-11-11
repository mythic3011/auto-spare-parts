<?php
declare(strict_types=1);

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');

require_once __DIR__ . '/resources/php/bootstrap.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

session_destroy();

$clearCookieNames = [
    'token',
    'csrf_token',
    'PHPSESSID',
    'username',
    'user',
    'role',
    'id',
    'cartId',
];

foreach ($clearCookieNames as $cookieName) {
    setcookie($cookieName, '', [
        'expires' => time() - 42000,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// Logout confirmation page
$pageTitle = 'Logout Successful';
$metaDescription = 'You have been logged out';
$showHeader = false;
$showFooter = false;
$basePath = '/';

// Start output buffering
ob_start();
?>

<div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
    <div class="text-center">
        <!-- Logo -->
        <div class="mb-8 animate-fadeIn">
            <img src="resources/image/SLMC_Icon_login.jpg" alt="Smart & Luxury Motor Company"
                 class="mx-auto w-32 h-32 rounded-lg shadow-lg">
        </div>
        
        <!-- Success Message -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 max-w-md mx-auto">
            <div class="mb-4">
                <svg class="mx-auto w-16 h-16 text-success-600 dark:text-success-400"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 13l4 4L19 7" />
                </svg>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Logged Out Successfully
            </h1>
            
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                You have been securely logged out from your account.
            </p>
            
            <p class="text-sm text-gray-500 dark:text-gray-500 mb-6">
                Redirecting to login page in <span id="countdown">5</span> seconds...
            </p>
            
            <!-- Action Links -->
            <div class="flex gap-4">
                <?php
                $buttonData = [
                    'text' => 'Login Again',
                    'href' => 'index.php',
                    'variant' => 'primary',
                    'class' => 'flex-1',
                ];
                include 'resources/templates/components/button.php';
                ?>
                
                <?php
                $buttonData = [
                    'text' => 'Go Home',
                    'href' => 'index.php',
                    'variant' => 'secondary',
                    'class' => 'flex-1',
                ];
                include 'resources/templates/components/button.php';
                ?>
            </div>
        </div>
        
        <!-- Security Notice -->
        <p class="text-xs text-gray-500 dark:text-gray-500 mt-8">
            For security, do not share your login credentials with anyone.
        </p>
    </div>
</div>

<!-- Dark mode toggle included in base layout -->

<?php
$content = ob_get_clean();

// Inline script for redirect
$inlineScript = <<<'JS'
<script>
    document.addEventListener('DOMContentLoaded', function() {
        sessionStorage.clear();
        localStorage.removeItem('token');
        localStorage.removeItem('userToken');
        localStorage.removeItem('cart');
        localStorage.removeItem('cartId');

        let countdown = 5;
        const countdownEl = document.getElementById('countdown');

        if (!countdownEl) {
            window.location.replace('index.php');
            return;
        }

        const timerId = window.setInterval(function() {
            countdown -= 1;
            countdownEl.textContent = String(Math.max(countdown, 0));

            if (countdown <= 0) {
                window.clearInterval(timerId);
                window.location.replace('index.php');
            }
        }, 1000);
    });
</script>

<style>
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeIn {
        animation: fadeIn 0.6s ease-out;
    }
</style>
JS;

// Load base template
include 'resources/templates/base.php';
?>
