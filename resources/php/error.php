<?php

declare(strict_types=1);

$allowedCodes = [
    403 => ['title' => '403 Forbidden', 'message' => 'You are not authorized to access this resource.'],
    404 => ['title' => '404 Not Found', 'message' => 'The requested page could not be found.'],
    500 => ['title' => '500 Internal Server Error', 'message' => 'An internal server error occurred.'],
    502 => ['title' => '502 Bad Gateway', 'message' => 'An upstream server error occurred.'],
    503 => ['title' => '503 Service Unavailable', 'message' => 'The service is temporarily unavailable.'],
    504 => ['title' => '504 Gateway Timeout', 'message' => 'Upstream service timeout occurred.'],
];

$code = (int) ($_GET['code'] ?? 500);
if (!isset($allowedCodes[$code])) {
    $code = 500;
}

http_response_code($code);
$title = $allowedCodes[$code]['title'];
$message = $allowedCodes[$code]['message'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> | Smart & Luxury Motor Company</title>
    <link href="/resources/image/SLMC_Icon_login.jpg" rel="icon" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp,container-queries"></script>
</head>
<body class="bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-white">
<main class="min-h-screen flex items-center justify-center px-4">
    <section class="max-w-lg w-full rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow p-8 text-center">
        <img alt="logo" class="mx-auto w-20 h-20 mb-4" src="/resources/image/SLMC_Icon_login.jpg" />
        <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-gray-600 dark:text-gray-300 mb-6"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <a class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-white font-semibold hover:bg-blue-700 transition"
           href="/index.php">Back to Home</a>
    </section>
</main>
</body>
</html>
