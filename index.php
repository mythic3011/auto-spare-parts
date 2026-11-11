<?php
require_once __DIR__ . '/resources/php/bootstrap.php';

$isDevEnv = ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production') === 'development';
error_reporting(E_ALL);
ini_set('display_errors', $isDevEnv ? '1' : '0');

require_once 'resources/php/class/Session.php';
require_once 'resources/php/class/CSRF.php';
require_once 'resources/php/class/Auth.php';

$session = new Session();
$csrf = new CSRF();
$csrfToken = $csrf->generateToken();

// Define development environment flag from APP_ENV instead of hard-coding it.
define('IS_DEV_ENV', $isDevEnv);

// Page configuration
$pageTitle = 'Login';
$metaDescription = 'Login to Smart & Luxury Motor Company management system';
$showHeader = false;
$showFooter = false;
$currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$isLocalHost = $currentHost === ''
    || str_contains($currentHost, 'localhost')
    || str_contains($currentHost, '127.0.0.1')
    || str_contains($currentHost, '.local')
    || str_contains($currentHost, '.test')
    || str_contains($currentHost, 'orb.local');
$enableTurnstile = !IS_DEV_ENV && !$isLocalHost;
$basePath = '/';
$turnstileSiteKey = $_ENV['TURNSTILE_SITE_KEY'] ?? getenv('TURNSTILE_SITE_KEY') ?: '0x4AAAAAAAdmnv0tiJzz_0nV';

$loginPageCss = <<<'CSS'
<style>
    html,
    body {
        min-height: 100%;
        margin: 0;
        background: #f8fafc !important;
    }

    html.dark,
    html[data-theme="dark"],
    html.dark body,
    html[data-theme="dark"] body {
        background: #020617 !important;
    }

    body {
        min-height: 100vh;
        overflow-x: hidden;
    }

    #app,
    .page-wrapper,
    main {
        background: transparent !important;
    }

    .login-page-shell {
        min-height: 100vh;
        width: 100vw;
        margin-left: calc(50% - 50vw);
        margin-right: calc(50% - 50vw);
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, 0.14), transparent 34rem),
            linear-gradient(135deg, #f8fafc 0%, #eef2ff 48%, #e0f2fe 100%);
        color: #0f172a;
    }

    .dark .login-page-shell,
    [data-theme="dark"] .login-page-shell {
        background:
            radial-gradient(circle at top, rgba(59, 130, 246, 0.16), transparent 42rem),
            linear-gradient(120deg, rgba(15, 23, 42, 0.92), rgba(2, 6, 23, 0.98));
        color: #ffffff;
    }

    .login-bg-layer {
        background:
            radial-gradient(circle at top, rgba(37, 99, 235, 0.12), transparent 42%),
            linear-gradient(120deg, rgba(248, 250, 252, 0.74), rgba(224, 242, 254, 0.72));
    }

    .dark .login-bg-layer,
    [data-theme="dark"] .login-bg-layer {
        background:
            radial-gradient(circle at top, rgba(59, 130, 246, 0.16), transparent 42%),
            linear-gradient(120deg, rgba(15, 23, 42, 0.92), rgba(2, 6, 23, 0.98));
    }

    .login-title-text {
        color: #0f172a;
    }

    .login-muted-text {
        color: #475569;
    }

    .dark .login-title-text,
    [data-theme="dark"] .login-title-text {
        color: #ffffff;
    }

    .dark .login-muted-text,
    [data-theme="dark"] .login-muted-text {
        color: #cbd5e1;
    }

    .login-logo-light {
        display: block;
    }

    .login-logo-dark {
        display: none;
    }

    .dark .login-logo-light,
    [data-theme="dark"] .login-logo-light {
        display: none;
    }

    .dark .login-logo-dark,
    [data-theme="dark"] .login-logo-dark {
        display: block;
    }

    #loginForm input[type="email"],
    #loginForm input[type="password"],
    #loginForm input[type="text"] {
        width: 100%;
        border-radius: 1rem;
        border: 1px solid #cbd5e1;
        background: rgba(248, 250, 252, 0.92);
        color: #0f172a;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        transition:
            border-color 160ms ease,
            box-shadow 160ms ease,
            background-color 160ms ease,
            transform 160ms ease;
    }

    #loginForm input[type="email"]:focus,
    #loginForm input[type="password"]:focus,
    #loginForm input[type="text"]:focus {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow:
            0 0 0 4px rgba(37, 99, 235, 0.14),
            0 8px 20px rgba(15, 23, 42, 0.08);
        outline: none;
        transform: translateY(-1px);
    }

    .dark #loginForm input[type="email"],
    .dark #loginForm input[type="password"],
    .dark #loginForm input[type="text"],
    [data-theme="dark"] #loginForm input[type="email"],
    [data-theme="dark"] #loginForm input[type="password"],
    [data-theme="dark"] #loginForm input[type="text"] {
        border-color: #334155;
        background: rgba(15, 23, 42, 0.9);
        color: #f8fafc;
    }

    .dark #loginForm input[type="email"]:focus,
    .dark #loginForm input[type="password"]:focus,
    .dark #loginForm input[type="text"]:focus,
    [data-theme="dark"] #loginForm input[type="email"]:focus,
    [data-theme="dark"] #loginForm input[type="password"]:focus,
    [data-theme="dark"] #loginForm input[type="text"]:focus {
        border-color: #60a5fa;
        background: #0f172a;
        box-shadow:
            0 0 0 4px rgba(96, 165, 250, 0.18),
            0 8px 20px rgba(0, 0, 0, 0.22);
    }

    #loginForm input::placeholder {
        color: #94a3b8;
    }

    #loginForm label {
        font-weight: 600;
    }

    #loginButton {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.65rem;
        overflow: hidden;
    }

    #loginButton::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.22), transparent);
        transform: translateX(-120%);
        transition: transform 420ms ease;
    }

    #loginButton:hover::before {
        transform: translateX(120%);
    }

    #loginButton[data-loading="true"]::after {
        content: "";
        width: 1rem;
        height: 1rem;
        border-radius: 9999px;
        border: 2px solid rgba(255, 255, 255, 0.45);
        border-top-color: #ffffff;
        animation: loginButtonSpin 700ms linear infinite;
    }

    #loginButton:disabled {
        cursor: wait;
        transform: none;
    }

    @keyframes loginButtonSpin {
        to {
            transform: rotate(360deg);
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(18px) scale(.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        #loginButton[data-loading="true"]::after {
            animation: none;
        }

        #loginForm input[type="email"]:focus,
        #loginForm input[type="password"]:focus,
        #loginForm input[type="text"]:focus {
            transform: none;
        }
    }

</style>
CSS;

// Start output buffering for content
ob_start();
?>

<div id="loginPage" class="login-page-shell relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-32 top-[-12rem] h-96 w-96 rounded-full bg-primary-500/20 blur-3xl"></div>
        <div class="absolute bottom-[-10rem] right-[-8rem] h-[32rem] w-[32rem] rounded-full bg-cyan-400/10 blur-3xl"></div>
        <div class="login-bg-layer absolute inset-0"></div>
    </div>

    <div class="relative mx-auto grid min-h-screen w-full max-w-7xl grid-cols-1 items-center gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
        <section class="hidden lg:block">
            <div class="max-w-xl animate-[fadeIn_700ms_ease-out_both]">
                <h1 class="login-title-text text-5xl font-black leading-tight tracking-tight xl:text-6xl">
                    Smart & Luxury Motor Company
                </h1>
                <p class="login-muted-text mt-6 max-w-lg text-base leading-7">
                    Sign in to continue to the SLMC dealer workspace.
                </p>
            </div>
        </section>

        <section class="mx-auto w-full max-w-md animate-[slideUp_650ms_ease-out_both]">
            <div class="mb-8 text-center lg:hidden">
                <img src="resources/image/SLMC_Icon.png" alt="Smart & Luxury Motor Company Logo" class="login-logo-light mx-auto h-20 w-20 rounded-2xl object-contain shadow-lg shadow-blue-950/40">
                <img src="resources/image/SLMC_Icon_DM.png" alt="Smart & Luxury Motor Company Logo" class="login-logo-dark mx-auto h-20 w-20 rounded-2xl object-contain shadow-lg shadow-blue-950/40">
                <h1 class="login-title-text mt-4 text-3xl font-extrabold tracking-tight">SLMC Portal</h1>
                <p class="login-muted-text mt-2 text-sm">Dealer workspace</p>
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-white/[0.08] p-1.5 shadow-2xl shadow-black/30 backdrop-blur-xl">
                <div class="rounded-[1.5rem] bg-white p-6 text-gray-900 shadow-xl dark:bg-gray-900/95 dark:text-white sm:p-7">
                    <div class="mb-7 flex items-center gap-4">
                        <img src="resources/image/SLMC_Icon.png" alt="Smart & Luxury Motor Company Logo" class="login-logo-light h-14 w-14 rounded-2xl object-contain shadow-md">
                        <img src="resources/image/SLMC_Icon_DM.png" alt="Smart & Luxury Motor Company Logo" class="login-logo-dark h-14 w-14 rounded-2xl object-contain shadow-md">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Welcome back</p>
                            <h2 class="text-2xl font-black tracking-tight text-gray-950 dark:text-white">Sign In</h2>
                        </div>
                    </div>

                    <div id="alertContainer" class="min-h-[0.25rem]"></div>

                    <form id="loginForm" action="resources/php/login.php" method="POST" class="space-y-4" autocomplete="on" novalidate>
                        <?php
                        $inputData = [
                            'name' => 'username',
                            'type' => 'email',
                            'label' => 'Email or Username',
                            'placeholder' => 'alex@auto-racing.com',
                            'required' => true,
                            'attributes' => ['autocomplete' => 'username']
                        ];
                        include 'resources/templates/components/input.php';
                        ?>

                        <?php
                        $inputData = [
                            'name' => 'password',
                            'type' => 'password',
                            'label' => 'Password',
                            'placeholder' => 'Enter your password',
                            'required' => true,
                            'attributes' => ['autocomplete' => 'current-password']
                        ];
                        include 'resources/templates/components/input.php';
                        ?>

                        <div id="login2FAField" class="hidden">
                            <?php
                            $inputData = [
                                'name' => 'login2FA',
                                'type' => 'text',
                                'label' => 'Two-Factor Authentication Code',
                                'placeholder' => 'Enter 6-digit code',
                                'attributes' => ['id' => 'login2FA', 'autocomplete' => 'one-time-code', 'inputmode' => 'numeric', 'maxlength' => '6', 'pattern' => '[0-9]{6}']
                            ];
                            include 'resources/templates/components/input.php';
                            ?>
                        </div>

                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/70">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember-me" name="remember-me" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                                <label for="remember-me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Remember me
                                </label>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">30 days</span>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                        <?php if ($enableTurnstile): ?>
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/70">
                                <div id="turnstile-widget" data-sitekey="<?= htmlspecialchars($turnstileSiteKey, ENT_QUOTES, 'UTF-8') ?>" data-theme="dark" data-size="wide" data-type="text"></div>
                                <input type="hidden" id="turnstileResponse" name="turnstile_response">
                                <div id="turnstile-error" class="mt-2 hidden text-sm text-danger-500"></div>
                            </div>
                        <?php else: ?>
                            <input type="hidden" id="turnstileResponse" name="turnstile_response" value="dev_environment_bypass">
                            <?php if ($isDevEnv): ?>
                                <p class="sr-only">Turnstile disabled in development environment.</p>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php
                        $buttonData = [
                            'text' => 'Sign In',
                            'type' => 'submit',
                            'variant' => 'primary',
                            'class' => 'w-full rounded-2xl py-3 text-base font-bold shadow-lg shadow-primary-600/20 transition hover:-translate-y-0.5',
                            'attributes' => ['id' => 'loginButton']
                        ];
                        include 'resources/templates/components/button.php';
                        ?>
                    </form>
                </div>
            </div>
            <div class="login-muted-text mt-6 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-center text-sm">
                <a href="privacy-policy.php" class="font-medium hover:text-primary-700 hover:underline dark:hover:text-primary-300">Privacy Policy</a>
                <a href="terms-of-use.php" class="font-medium hover:text-primary-700 hover:underline dark:hover:text-primary-300">Terms of Use</a>
                <a href="qa.php" class="font-medium hover:text-primary-700 hover:underline dark:hover:text-primary-300">Q&amp;A</a>
                <a href="about.php" class="font-medium hover:text-primary-700 hover:underline dark:hover:text-primary-300">About SLMC</a>
            </div>
        </section>
    </div>
</div>

<?php
if (in_array(getenv('APP_ENV'), ['development', 'testing'], true)) {
    include __DIR__ . '/resources/templates/demo-accounts.php';
}
$content = ob_get_clean();
$content = $loginPageCss . $content;

// Additional JS for login functionality
$turnstileSiteKeyJson = json_encode($turnstileSiteKey, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
$inlineScript = <<<JS
<script>
    const TURNSTILE_SITE_KEY = {$turnstileSiteKeyJson};
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const alertContainer = document.getElementById('alertContainer');
        const login2FA = document.getElementById('login2FA');
        const login2FAContainer = document.getElementById('login2FAField');

        function syncLoginTheme() {
            const storedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const shouldUseDark = storedTheme === 'dark' || (!storedTheme && prefersDark);

            document.documentElement.classList.toggle('dark', shouldUseDark);
            document.documentElement.dataset.theme = shouldUseDark ? 'dark' : 'light';
        }

        syncLoginTheme();

        function getLoginPayload(data) {
            return data?.result?.result || data?.result || data?.data || data || {};
        }

        function setButtonLoading(isLoading) {
            if (!loginButton) {
                return;
            }

            loginButton.disabled = isLoading;
            loginButton.textContent = isLoading ? 'Signing in...' : 'Sign In';
            loginButton.dataset.loading = String(isLoading);
            loginButton.classList.toggle('cursor-wait', isLoading);
            loginButton.classList.toggle('opacity-80', isLoading);
        }

        function revealTwoFactorField() {
            login2FAContainer?.classList.remove('hidden');
            login2FA?.setAttribute('required', 'required');
            login2FA?.focus();
        }

        // --- BEGIN PATCHED TURNSTILE INIT ---
        let turnstileWidgetId = null;
        function showTurnstileError(message) {
            const errorDiv = document.getElementById('turnstile-error');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
            }
        }
        function clearTurnstileError() {
            const errorDiv = document.getElementById('turnstile-error');
            if (errorDiv) {
                errorDiv.textContent = '';
                errorDiv.classList.add('hidden');
            }
        }
        function initTurnstile() {
            const widget = document.getElementById('turnstile-widget');
            const responseInput = document.getElementById('turnstileResponse');

            if (!responseInput || !widget || turnstileWidgetId !== null) {
                return;
            }

            if (typeof turnstile === 'undefined') {
                responseInput.value = '';
                showTurnstileError('Verification is not ready. Refresh the page or try again.');
                return;
            }

            clearTurnstileError();

            try {
                turnstileWidgetId = turnstile.render(widget, {
                    sitekey: TURNSTILE_SITE_KEY,
                    theme: 'dark',
                    size: 'wide',
                    callback: function(token) {
                        responseInput.value = token;
                        clearTurnstileError();
                    },
                    'expired-callback': function() {
                        responseInput.value = '';
                        showTurnstileError('Verification expired. Please verify again.');
                    },
                    'error-callback': function() {
                        responseInput.value = '';
                        showTurnstileError('Verification could not be loaded. Check the allowed domain for this Turnstile site key.');
                    }
                });
            } catch (error) {
                responseInput.value = '';
                showTurnstileError('Verification could not be loaded. Check the allowed domain for this Turnstile site key.');
            }
        }

        function resetTurnstile() {
            const responseInput = document.getElementById('turnstileResponse');

            if (responseInput && responseInput.value !== 'dev_environment_bypass') {
                responseInput.value = '';
            }

            if (typeof turnstile !== 'undefined' && turnstileWidgetId !== null) {
                turnstile.reset(turnstileWidgetId);
                clearTurnstileError();
            }
        }

        window.setTimeout(initTurnstile, 0);
        // --- END PATCHED TURNSTILE INIT ---

        // Show alert message without injecting user-controlled HTML.
        function showAlert(type, message, title = null) {
            if (!alertContainer) {
                return;
            }

            const isError = type === 'error';
            const toneClasses = isError
                ? 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200'
                : 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200';

            const wrapper = document.createElement('div');
            wrapper.className = 'mb-4 rounded-2xl border p-4 shadow-sm transition ' + toneClasses;

            const row = document.createElement('div');
            row.className = 'flex items-start gap-3';

            const icon = document.createElement('span');
            icon.className = 'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold ' + (isError ? 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-100' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-800 dark:text-emerald-100');
            icon.textContent = isError ? '!' : '✓';

            const textWrapper = document.createElement('div');
            textWrapper.className = 'min-w-0';

            if (title) {
                const strong = document.createElement('p');
                strong.className = 'text-sm font-bold';
                strong.textContent = title;
                textWrapper.appendChild(strong);
            }

            const text = document.createElement('p');
            text.className = 'text-sm';
            text.textContent = message || 'Unknown error';
            textWrapper.appendChild(text);

            row.append(icon, textWrapper);
            wrapper.appendChild(row);
            alertContainer.replaceChildren(wrapper);
            alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Handle login form submission
        if (loginForm && loginButton) {
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                setButtonLoading(true);
                alertContainer?.replaceChildren();

                try {
                    const formData = new FormData(loginForm);
                    const response = await fetch('resources/php/login.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'include'
                    });

                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }

                    const data = await response.json();
                    const loginData = getLoginPayload(data);

                    if (data.status === 'error' || loginData.error) {
                        throw new Error(data.message || loginData.error || 'Login failed');
                    }

                    if (!loginData || Object.keys(loginData).length === 0) {
                        throw new Error(data.message || 'Login failed');
                    }

                    if (document.getElementById('remember-me')?.checked) {
                        localStorage.setItem('username', loginData.username || loginData.email || '');
                        localStorage.setItem('remember-me', 'true');
                    } else {
                        localStorage.removeItem('username');
                        localStorage.removeItem('remember-me');
                    }

                    if (loginData.requires2FA || loginData.page === '/2fa') {
                        revealTwoFactorField();
                        showAlert('success', 'Enter your two-factor authentication code to continue.', 'Verification required');
                        return;
                    }

                    if (typeof loginData.token !== 'string' || !loginData.token.trim()) {
                        throw new Error('Login response did not include an authentication token.');
                    }
                    window.SLMC_AUTH.storeToken(loginData.token, Boolean(document.getElementById('remember-me')?.checked));
                    showAlert('success', 'Login successful. Redirecting...', 'Success');

                    setTimeout(() => {
                        if (loginData.role === 'Sales Manager') {
                            window.location.href = 'page/Staff/order.html';
                            return;
                        }

                        window.location.href = loginData.page || 'page/Dealer/viewSparePart.php';
                    }, 900);
                } catch (error) {
                    if (['localhost', '127.0.0.1'].includes(window.location.hostname) || window.location.hostname.endsWith('.local')) {
                        console.error('Login error:', error);
                    }
                    showAlert('error', error.message || 'An error occurred. Please try again.', 'Error');
                    resetTurnstile();
                } finally {
                    setButtonLoading(false);
                }
            });
        }

        // Load remembered username
        const rememberedUsername = localStorage.getItem('username');
        const rememberMeCheckbox = document.getElementById('remember-me');
        if (rememberedUsername) {
            const usernameInput = document.getElementById('username');

            if (usernameInput) {
                usernameInput.value = rememberedUsername;
            }

            if (rememberMeCheckbox) {
                rememberMeCheckbox.checked = true;
            }
        }

    });
</script>

JS;

// Load base template
include 'resources/templates/base.php';