<?php
$basePath = $basePath ?? '';
$pageTitleText = isset($pageTitle) && trim((string) $pageTitle) !== ''
    ? trim((string) $pageTitle) . ' | Smart & Luxury Motor Company'
    : 'Smart & Luxury Motor Company';
$metaDescriptionText = isset($metaDescription) && trim((string) $metaDescription) !== ''
    ? trim((string) $metaDescription)
    : 'Smart & Luxury Motor Company dealer portal';
$canonicalUrl = $canonicalUrl ?? '';
$lightIcon = $basePath . 'resources/image/SLMC_Icon.png';
$darkIcon = $basePath . 'resources/image/SLMC_Icon_DM.png';
$lightLogo = $basePath . 'resources/image/SLMC_LOGO.png';
$darkLogo = $basePath . 'resources/image/SLMC_LOGO_DM.png';
$ogImage = $ogImage ?? $lightIcon;
$robotsMeta = $robotsMeta ?? 'noindex, nofollow';
$showPageLoader = $showPageLoader ?? true;
$deferAdditionalJS = $deferAdditionalJS ?? true;
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo isset($darkMode) && $darkMode ? 'dark' : ''; ?>" data-theme="<?php echo isset($darkMode) && $darkMode ? 'dark' : 'light'; ?>">
<head>
    <script src="/resources/js/theme.global.js"></script>
    <style>
        .page-loader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: grid;
            place-items: center;
            background: var(--color-app-bg);
            color: var(--color-app-text);
            opacity: 1;
            visibility: visible;
            transition: opacity 180ms ease, visibility 180ms ease;
        }

        .page-loader.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .page-loader__spinner {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 9999px;
            border: 3px solid var(--color-app-border);
            border-top-color: var(--color-brand);
            animation: pageLoaderSpin 750ms linear infinite;
        }

        @keyframes pageLoaderSpin {
            to {
                transform: rotate(360deg);
            }
        }

        img[loading="lazy"] {
            content-visibility: auto;
        }

        html.theme-transition,
        html.theme-transition *,
        html.theme-transition *::before,
        html.theme-transition *::after {
            transition:
                background-color 180ms ease,
                border-color 180ms ease,
                color 180ms ease,
                fill 180ms ease,
                stroke 180ms ease,
                box-shadow 180ms ease,
                opacity 180ms ease !important;
        }

        @media (prefers-reduced-motion: reduce) {
            html.theme-transition,
            html.theme-transition *,
            html.theme-transition *::before,
            html.theme-transition *::after {
                transition: none !important;
            }

            .page-loader__spinner {
                animation: none;
            }
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="<?= htmlspecialchars($metaDescriptionText, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="author" content="Smart &amp; Luxury Motor Company">
    <meta name="robots" content="<?= htmlspecialchars($robotsMeta, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="referrer" content="same-origin">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#020617" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#f8fafc" media="(prefers-color-scheme: light)">
    <meta name="application-name" content="SLMC Portal">
    <meta name="apple-mobile-web-app-title" content="SLMC Portal">
    <meta name="format-detection" content="telephone=no">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Smart &amp; Luxury Motor Company">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitleText, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescriptionText, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitleText, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescriptionText, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">

    <?php if ($canonicalUrl !== ''): ?>
        <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <title><?= htmlspecialchars($pageTitleText, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="icon" href="<?= htmlspecialchars($lightIcon, ENT_QUOTES, 'UTF-8') ?>" type="image/png" media="(prefers-color-scheme: light)">
    <link rel="icon" href="<?= htmlspecialchars($darkIcon, ENT_QUOTES, 'UTF-8') ?>" type="image/png" media="(prefers-color-scheme: dark)">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($lightIcon, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="preload" as="image" href="<?= htmlspecialchars($lightIcon, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="preload" as="image" href="<?= htmlspecialchars($darkIcon, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="preconnect" href="https://challenges.cloudflare.com" crossorigin>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>resources/css/portal.css">

    <!-- Tailwind Config -->


    <script src="/resources/js/auth-session.global.js"></script>

    <!-- Additional CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ((array)$additionalCSS as $css): ?>
            <link href="<?= htmlspecialchars((string) $css, ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Cloudflare Turnstile -->
    <?php if (isset($enableTurnstile) && $enableTurnstile): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
</head>
<body class="portal-page bg-app-bg text-app-text font-sans antialiased">
    <!-- Header/Navigation -->
    <?php if (isset($showHeader) && $showHeader): ?>
        <?php include __DIR__ . '/header.php'; ?>
    <?php endif; ?>

    <?php if ($showPageLoader): ?>
        <div id="pageLoader" class="page-loader" role="status" aria-live="polite" aria-label="Loading page">
            <div class="page-loader__spinner" aria-hidden="true"></div>
        </div>
    <?php endif; ?>

    <main id="pageMain" class="<?= htmlspecialchars((string) ($containerClass ?? 'container mx-auto px-4 py-8'), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (isset($content)): ?>
            <?php echo $content; ?>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php if (isset($showFooter) && $showFooter): ?>
        <?php include __DIR__ . '/footer.php'; ?>
    <?php endif; ?>

    <!-- Dark Mode Toggle -->
    <button id="darkModeToggle"
            type="button"
            class="fixed bottom-4 right-4 z-50 inline-flex h-11 w-11 items-center justify-center rounded-full border border-app-border bg-app-surface text-app-muted shadow-soft transition hover:-translate-y-0.5 hover:text-brand dark:shadow-soft-dark"
            title="Toggle theme" aria-label="Toggle theme" aria-pressed="false">
        <svg data-theme-icon="light" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 18a6 6 0 100-12 6 6 0 000 12z" />
            <path fill-rule="evenodd" d="M12 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm0 17a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM4.222 4.222a1 1 0 011.414 0l.707.707A1 1 0 114.93 6.343l-.707-.707a1 1 0 010-1.414zm12.728 12.728a1 1 0 011.414 0l.707.707a1 1 0 11-1.414 1.414l-.707-.707a1 1 0 010-1.414zM2 12a1 1 0 011-1h1a1 1 0 110 2H3a1 1 0 01-1-1zm17 0a1 1 0 011-1h1a1 1 0 110 2h-1a1 1 0 01-1-1zM4.222 19.778a1 1 0 010-1.414l.707-.707a1 1 0 111.414 1.414l-.707.707a1 1 0 01-1.414 0zM17.657 6.343a1 1 0 010-1.414l.707-.707a1 1 0 111.414 1.414l-.707.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
        </svg>
        <svg data-theme-icon="dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M17.293 14.293A8 8 0 019.707 6.707a8.001 8.001 0 1010.586 10.586 1 1 0 00-1.414-1.414 6 6 0 01-1.586-1.586z" clip-rule="evenodd" />
        </svg>
    </button>

    <!-- Scripts -->
    <script>
        // Dark mode initialization
        function initDarkMode() {
            const html = document.documentElement;
            const storedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const shouldUseDark = storedTheme === 'dark' || (!storedTheme && prefersDark);

            html.classList.toggle('dark', shouldUseDark);
            html.dataset.theme = shouldUseDark ? 'dark' : 'light';
        }

        initDarkMode();

        function syncThemeToggleState() {
            const toggle = document.getElementById('darkModeToggle');
            const isDark = document.documentElement.classList.contains('dark');

            if (!toggle) {
                return;
            }

            toggle.setAttribute('aria-pressed', String(isDark));
            toggle.setAttribute('title', isDark ? 'Switch to light theme' : 'Switch to dark theme');
            toggle.setAttribute('aria-label', isDark ? 'Switch to light theme' : 'Switch to dark theme');
        }

        syncThemeToggleState();

        function hidePageLoader() {
            const loader = document.getElementById('pageLoader');

            if (!loader) {
                return;
            }

            loader.classList.add('is-hidden');
            window.setTimeout(() => loader.remove(), 220);
        }

        function applyLazyMediaDefaults() {
            document.querySelectorAll('img:not([loading])').forEach((image) => {
                image.setAttribute('loading', 'lazy');
                image.setAttribute('decoding', 'async');
            });

            document.querySelectorAll('iframe:not([loading])').forEach((frame) => {
                frame.setAttribute('loading', 'lazy');
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyLazyMediaDefaults, { once: true });
            window.addEventListener('load', hidePageLoader, { once: true });
        } else {
            applyLazyMediaDefaults();
            hidePageLoader();
        }

        function applyThemeTransition() {
            const html = document.documentElement;

            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            html.classList.add('theme-transition');
            window.setTimeout(() => {
                html.classList.remove('theme-transition');
            }, 220);
        }


    </script>

    <!-- Additional Scripts -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ((array)$additionalJS as $js): ?>
            <script src="<?= htmlspecialchars((string) $js, ENT_QUOTES, 'UTF-8') ?>"<?= $deferAdditionalJS ? ' defer' : '' ?>></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($inlineScript)): ?>
        <?php echo $inlineScript; ?>
    <?php endif; ?>
</body>
</html>
