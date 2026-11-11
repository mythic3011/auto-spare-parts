<?php

declare(strict_types=1);

require_once './class/Session.php';
require_once './class/User.php';
require_once './class/CSRF.php';
require_once './class/Auth.php';
require_once './class/ResponseHandler.php';
require_once './class/Api/ApiBootstrap.php';
require_once './class/Api/ApiRequest.php';
require_once './class/Api/ApiAuthContext.php';
require_once './class/Api/ApiResponder.php';
require_once './class/Api/RouteRegistry.php';

ApiBootstrap::init();

$userModel = new User();
$auth = new Auth();
$request = new ApiRequest();
$authContext = new ApiAuthContext();
$registry = new RouteRegistry();

/**
 * @return array<string, string>
 */
function parseAddress(string $address): array
{
    $parts = array_map('trim', explode(',', $address));
    $count = count($parts);

    $result = [
        'addressLine1' => '',
        'addressLine2' => '',
        'district' => '',
        'city' => '',
        'stateProvince' => '',
        'country' => '',
    ];

    if ($count >= 1) {
        $result['addressLine1'] = $parts[0];
    }
    if ($count >= 2) {
        $result['addressLine2'] = $parts[1];
    }
    if ($count >= 3) {
        $result['district'] = $parts[2];
    }
    if ($count >= 4) {
        $result['city'] = $parts[3];
    }
    if ($count >= 5) {
        $result['country'] = $parts[4];
    }

    return $result;
}

/**
 * @param array<string, mixed> $userData
 * @return array<string, mixed>
 */
function mapProfileResponse(array $userData): array
{
    $names = explode(' ', (string) ($userData['contactName'] ?? ''), 2);
    $addressParts = parseAddress((string) ($userData['deliveryAddress'] ?? ''));

    return [
        'username' => (string) ($userData['UserID'] ?? ''),
        'FullName' => (string) ($userData['contactName'] ?? ''),
        'email' => (string) ($userData['UserID'] ?? ''),
        'firstName' => $names[0] ?? '',
        'lastName' => $names[1] ?? '',
        'mobileNumber' => (string) ($userData['contactNumber'] ?? ''),
        'faxNumber' => (string) ($userData['faxNumber'] ?? ''),
        'addressLine1' => $addressParts['addressLine1'],
        'addressLine2' => $addressParts['addressLine2'],
        'stateProvince' => $addressParts['stateProvince'],
        'district' => $addressParts['district'],
        'city' => $addressParts['city'],
        'country' => $addressParts['country'],
    ];
}

$registry->add(
    'profile.get',
    ['GET'],
    static function () use ($authContext, $auth, $request, $userModel): void {
        $authUser = $authContext->requireUser($auth, $request);
        $userId = $authContext->userIdentifier($authUser);

        $userData = $userModel->getUser($userId);
        if (!$userData) {
            throw new RuntimeException('User not found', 404);
        }

        ResponseHandler::success(mapProfileResponse($userData));
    },
);
$registry->alias('get', 'profile.get');

$registry->add(
    'profile.edit',
    ['POST'],
    static function () use ($authContext, $auth, $request, $userModel): void {
        $authUser = $authContext->requireUser($auth, $request);
        $userId = $authContext->userIdentifier($authUser);
        $data = $request->jsonBody();

        $userData = $userModel->getUser($userId);
        if (!$userData) {
            throw new RuntimeException('User not found', 404);
        }

        $updateData = [];
        foreach ($data as $key => $value) {
            if (isset($userData[$key]) && $userData[$key] != $value) {
                $updateData[$key] = $value;
            }
        }

        if (!empty($updateData)) {
            $userModel->updateProfile($userId, $updateData);
            ResponseHandler::success(['success' => true, 'message' => 'Profile updated successfully']);
        }

        ResponseHandler::success(['success' => true, 'message' => 'No changes detected']);
    },
);
$registry->alias('edit', 'profile.edit');

$registry->add(
    'profile.update_password',
    ['POST'],
    static function () use ($authContext, $auth, $request, $userModel): void {
        $authUser = $authContext->requireUser($auth, $request);
        $userId = $authContext->userIdentifier($authUser);
        $data = $request->jsonBody();

        $currentPassword = (string) ($data['currentPassword'] ?? '');
        $newPassword = (string) ($data['newPassword'] ?? '');
        $confirmPassword = (string) ($data['confirmPassword'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            ResponseHandler::error('All password fields are required', 400, ['type' => 'password']);
        }

        $userData = $userModel->getUser($userId);
        if (!$userData) {
            throw new RuntimeException('User not found', 404);
        }

        if (!password_verify($currentPassword, (string) ($userData['password'] ?? ''))) {
            ResponseHandler::error('Current password is incorrect', 400, ['type' => 'password']);
        }

        if ($currentPassword === $newPassword) {
            ResponseHandler::error('New password must be different from the current password', 400, ['type' => 'password']);
        }

        if ($newPassword !== $confirmPassword) {
            ResponseHandler::error('New password and confirm password do not match', 400, ['type' => 'password']);
        }

        $userModel->updatePassword($userId, $currentPassword, $newPassword);
        ResponseHandler::success(['success' => true, 'message' => 'Password updated successfully']);
    },
);
$registry->alias('update_password', 'profile.update_password');

ApiResponder::run(
    static function () use ($registry, $request): void {
        $action = $request->action();
        if ($action === '') {
            throw new RuntimeException('Invalid action', 400);
        }

        $registry->dispatch($action, $request->method());
    },
    'Profile request failed',
);
