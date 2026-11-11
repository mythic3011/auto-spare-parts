<?php

declare(strict_types=1);

/**
 * Bootstrap and Autoloader for Auto Spare Parts
 *
 * This file handles:
 * - Environment variable loading
 * - PSR-4 autoloading
 * - Global configuration setup
 */

// Define root path
define('APP_ROOT', __DIR__);

// Load configuration before evaluating debug settings.
loadEnvironmentVariables();

if (getenv('APP_DEBUG') === 'true') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Register PSR-4 autoloader
registerAutoloader();

/**
 * Load environment variables from .env file
 */
function loadEnvironmentVariables(): void
{
    $envFile = APP_ROOT . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($envFile)) {
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines ?? [] as $line) {
        if (str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2) + [null, null];
        if ($key !== null && $value !== null) {
            $key = trim($key);
            $value = trim($value);
            // Deployment and test environment values take precedence over .env.
            if (getenv($key) === false) {
                putenv(sprintf('%s=%s', $key, $value));
            }
        }
    }
}

/**
 * Register PSR-4 autoloader
 */
function registerAutoloader(): void
{
    spl_autoload_register(function (string $class): void {
        // Project namespace
        $prefix = 'App\\';
        $baseDir = APP_ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR;

        // Check if class uses the project namespace
        if (strpos($class, $prefix) === 0) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        }

        // Legacy support for classes without namespace in class directory
        $legacyPath = APP_ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . $class . '.php';
        if (file_exists($legacyPath)) {
            require_once $legacyPath;
        }
    });
}

// Helper function to get configuration
/**
 * @return array<string, mixed>
 */
function getConfig(): array
{
    static $config = null;

    if (isset($GLOBALS['APP_CONFIG']) && is_array($GLOBALS['APP_CONFIG'])) {
        return $GLOBALS['APP_CONFIG'];
    }

    if ($config === null) {
        $configFile = APP_ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'class' . DIRECTORY_SEPARATOR . 'config.php';
        $loaded = require $configFile;
        $config = is_array($loaded) ? $loaded : ($GLOBALS['APP_CONFIG'] ?? []);
    }

    return $config;
}

// Helper function to get environment variable with default
function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return $value !== false ? $value : $default;
}
