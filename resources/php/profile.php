<?php

declare(strict_types=1);

require_once './class/User.php';
require_once './class/HttpJson.php';
require_once './class/ValidationHelper.php';
require_once './class/Api/ApiRequest.php';
require_once './class/Api/ApiBootstrap.php';

ApiBootstrap::init();

$request = new ApiRequest();
$userModel = new User();

try {
    $currentUser = $userModel->requireCurrentUser();
} catch (RuntimeException) {
    HttpJson::send(['status' => 'failed', 'error' => 'Authentication required'], 401);
}

$userId = (string) ($currentUser['id'] ?? '');
$action = $request->scalar('action');
$method = $request->method();

if (in_array($action, ['updateProfile', 'updatePassword'], true) && $method !== 'POST') {
    HttpJson::send(['status' => 'failed', 'error' => 'Invalid request method'], 405);
}

try {
    match ($action) {
        'getInfo', 'getEditInfo' => HttpJson::send(readProfile($userModel, $userId)),
        'updateProfile' => HttpJson::send(saveProfile($userModel, $userId, $request)),
        'updatePassword' => HttpJson::send(savePassword($userModel, $userId, $request)),
        default => HttpJson::send(['error' => 'Invalid action'], 400),
    };
} catch (InvalidArgumentException $exception) {
    HttpJson::send(['status' => 'failed', 'error' => $exception->getMessage()], 400);
} catch (Throwable $exception) {
    error_log('Profile request failed: ' . $exception->getMessage());
    HttpJson::send(['status' => 'failed', 'error' => 'Profile request failed'], 400);
}

/**
 * @return array<string, string>
 */
function readProfile(User $userModel, string $userId): array
{
    $userData = $userModel->getUser($userId);
    if ($userData === null) {
        throw new InvalidArgumentException('User not found');
    }

    return [
        'name' => (string) ($userData['contactName'] ?? ''),
        'contactNumber' => (string) ($userData['contactNumber'] ?? ''),
        'faxNumber' => (string) ($userData['faxNumber'] ?? ''),
        'address' => (string) ($userData['deliveryAddress'] ?? ''),
        'email' => (string) ($userData['UserID'] ?? $userId),
    ];
}

/**
 * @return array<string, string>
 */
function saveProfile(User $userModel, string $userId, ApiRequest $request): array
{
    $updated = $userModel->updateProfile($userId, [
        'contactName' => ValidationHelper::boundedText($request->scalar('name'), 100),
        'contactNumber' => ValidationHelper::digits($request->scalar('mobileNumber')),
        'faxNumber' => ValidationHelper::digits($request->scalar('faxNumber'), false),
        'deliveryAddress' => ValidationHelper::boundedText($request->scalar('addressLine1'), 255),
    ]);

    return ['status' => $updated ? 'success' : 'failed'];
}

/**
 * @return array<string, string>
 */
function savePassword(User $userModel, string $userId, ApiRequest $request): array
{
    $updated = $userModel->updatePassword(
        $userId,
        $request->scalar('oldPassword'),
        $request->scalar('newPassword')
    );

    return ['status' => $updated ? 'success' : 'failed'];
}
