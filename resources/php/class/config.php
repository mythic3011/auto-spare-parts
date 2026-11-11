<?php

declare(strict_types=1);

// Load environment variables from .env file if it exists
// Only set values that aren't already in the environment (allows test overrides)
$envFile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.env';
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach (is_array($lines) ? $lines : [] as $line) {
        if (str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        // Don't overwrite values already set in the environment.
        if (getenv($key) === false) {
            putenv(sprintf('%s=%s', $key, trim($value)));
        }
    }
}

if (!defined('CONFIG')) {
    define('CONFIG', [
        'origins' => explode(',', getenv('ALLOWED_ORIGINS') ?: 'http://localhost'),
        'dev_token' => getenv('DEV_TOKEN') ?: '',
        'api_base_url' => getenv('API_BASE_URL') ?: 'http://localhost',
        'turnstile_enabled' => (bool)(getenv('TURNSTILE_ENABLED') ?: false),
        'turnstile_secret' => getenv('TURNSTILE_SECRET_KEY') ?: '',
        'cors_max_age' => (int) (getenv('CORS_MAX_AGE') ?: 600),
    ]);
}

$environment = getenv('APP_ENV') ?: 'development';
$requestTestEnv = strtolower((string)($_SERVER['HTTP_X_TEST_ENV'] ?? $_SERVER['HTTP_X_TESTING'] ?? ''));
// A client header must not be able to retarget a production database.
$allowTestDatabaseHeader = in_array($environment, ['testing', 'development'], true);
$isTestRequest = $allowTestDatabaseHeader
    && in_array($requestTestEnv, ['testing', 'true', '1'], true);

// Auto-detect Docker: explicit env var takes priority, otherwise probe the mariadb hostname
$dockerEnv = getenv('DOCKER_ENV');
if ($dockerEnv !== false) {
    $isDocker = filter_var($dockerEnv, FILTER_VALIDATE_BOOLEAN);
} else {
    $isDocker = gethostbyname('mariadb') !== 'mariadb';
}

$queryCacheEnv = getenv('DB_QUERY_CACHE');
$config = [
    'app_env'  => $environment,
    'app_debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'db_port'  => (int)(getenv('DB_PORT') ?: 3306),
    'db_driver' => getenv('DB_DRIVER') ?: 'mysqli',
    'db_charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'db_query_cache' => $queryCacheEnv === false
        ? $environment !== 'testing'
        : filter_var($queryCacheEnv, FILTER_VALIDATE_BOOLEAN),
    'db_query_cache_size' => (int) (getenv('DB_QUERY_CACHE_SIZE') ?: 64),
];

if ($isDocker) {
    $config['db_host'] = getenv('DB_DOCKER_HOST') ?: 'mariadb';
    $config['db_user'] = getenv('DB_DOCKER_USER') ?: 'root';
    $config['db_pass'] = getenv('DB_DOCKER_PASSWORD') ?: 'rootpassword';
    $config['db_name'] = $isTestRequest
        ? 'projectdb_test'
        : (getenv('DB_DOCKER_NAME') ?: 'projectDB');
} else {
    $config['db_host'] = getenv('DB_HOST') ?: 'localhost';
    $config['db_user'] = getenv('DB_USER') ?: 'root';
    $config['db_pass'] = getenv('DB_PASSWORD') ?: '';
    $config['db_name'] = $isTestRequest
        ? 'projectdb_test'
        : (getenv('DB_NAME') ?: 'projectdb');
}

return $config;
