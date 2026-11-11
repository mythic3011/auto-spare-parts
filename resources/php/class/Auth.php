<?php
// Auth.php

require_once 'Database.php';
require_once 'RoleName.php';
require_once 'RequestToken.php';

class Auth {
    private $db;
    private const TOKEN_EXPIRATION = 3600; // 1 hour in seconds

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($username, $password) {
        require_once __DIR__ . '/User.php';
        $user = (new User())->getUser((string) $username);
        $storedHash = (string) ($user['password'] ?? '');
        if ($user === null || $storedHash === '' || !password_verify($password, $storedHash)) {
            return ['error' => 'Invalid username or password'];
        }

        $token = $this->generateToken($user['UserID'], $user['UserRole']);

        return [
            'result' => [
                'token' => $token,
                'username' => $user['UserID'],
                'role' => $user['UserRole'],
                'page' => $this->getPageForRole($user['UserRole'])
            ]
        ];
    }

    public function logout($token) {
        // Invalidate the token
        $this->db->query("DELETE FROM user_tokens WHERE token = ?", [$token]);
        return ['message' => 'Logged out successfully'];
    }

    public function authenticateRequest() {
        $token = $this->getBearerToken();
        if (!$token) {
            return null;
        }

        $userData = $this->validateToken($token);
        if (!$userData) {
            return null;
        }

        return [
            'id' => $userData['UserID'],
            'username' => $userData['UserID'],
            'role' => $userData['UserRole']
        ];
    }

    private function generateToken($userId, $userRole) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = time() + self::TOKEN_EXPIRATION;

        $this->db->query(
            "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$userId, $token, date('Y-m-d H:i:s', $expiresAt)]
        );

        return $token;
    }

    public function validateToken($token) {
        // remove 'Bearer '
        $token = str_replace('Bearer ', '', $token);
        $token = trim($token);

        $result = $this->db->query(
            "SELECT u.* FROM user_tokens t JOIN UserRoles u ON t.user_id = u.UserID 
         WHERE t.token = ? AND t.expires_at > NOW()",
            [$token]
        );

        if (!$result || !isset($result[0]) || !is_array($result[0])) {
            return null;
        }

        unset($result[0]['password'], $result[0]['Password']);

        return $result[0];
    }

    public function getBearerToken() {
        return RequestToken::fromAuthorizationHeader();
    }

    private function getPageForRole($role) {
        return RoleName::home((string) $role);
    }
}
