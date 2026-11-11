<?php

require_once 'Database.php';
require_once 'Session.php';
require_once 'RoleName.php';
require_once 'ValidationHelper.php';

class User
{
    private Database $db;
    private Session $session;
    private const PASSWORD_MIN_LENGTH = 8;
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutes

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
    }

    public function logLoginAttempt(string $username, bool $success): void
    {
        $ip_address = $this->getClientIpAddress();
        $success_int = $success ? 1 : 0;
        $this->db->query(
            "INSERT INTO login_attempts (username, ip_address, attempt_time, success) VALUES (?, ?, NOW(), ?)",
            [$username, $ip_address, $success_int]
        );
    }

    public function getPageForRole(string $role): string
    {
        return RoleName::home($role);
    }

    /**
     * @param array<string, mixed> $loginResult
     * @return array<string, mixed>
     */
    public function establishLogin(array $loginResult, string $username): array
    {
        $result = is_array($loginResult['result'] ?? null) ? $loginResult['result'] : [];
        $role = (string) ($result['role'] ?? '');
        $id = (string) ($result['username'] ?? $username);
        $result['page'] = RoleName::home($role);
        $result['username'] = $id;
        $loginResult['result'] = $result;

        $this->session->setUser([
            'id' => $id,
            'username' => $id,
            'role' => $role,
        ]);
        $this->rememberUsername($id);
        $this->logLoginAttempt($username, true);

        return $loginResult;
    }

    public function updateProfile(string $userId, array $data): bool
    {
        $userId = trim($userId);
        $user = $this->getCurrentUser();

        if ($userId === '' || !$user || (string) ($user['id'] ?? '') !== $userId) {
            return false;
        }

        $role = (string) ($user['role'] ?? '');
        $isDealer = $this->isDealerRole($role);
        $table = $isDealer ? 'Dealer' : 'SalesManager';
        $idField = $isDealer ? 'dealerID' : 'salesManagerID';

        $allowedFields = $isDealer
            ? ['contactName', 'contactNumber', 'faxNumber', 'deliveryAddress']
            : ['managerName', 'contactNumber'];

        $updates = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = trim((string) $data[$field]);

            if ($this->validateInput($value)) {
                $updates[] = "$field = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $userId;
        $query = "UPDATE $table SET " . implode(', ', $updates) . " WHERE $idField = ?";

        $result = $this->db->query($query, $params);
        if ($result) {
            $this->updateSessionUser($userId);
        }
        return $result !== false;
    }


    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function updatePassword(string $userId, string $currentPassword, string $newPassword): bool
    {
        $userId = trim($userId);
        $user = $this->getCurrentUser();

        if ($userId === '' || !$user || (string) ($user['id'] ?? '') !== $userId) {
            return false;
        }

        if ($currentPassword === '' || $newPassword === '' || hash_equals($currentPassword, $newPassword)) {
            return false;
        }

        $dbUser = $this->getUser($userId);
        $storedHash = (string) ($dbUser['password'] ?? '');

        if ($dbUser === null || $storedHash === '' || !$this->verifyPassword($currentPassword, $storedHash)) {
            return false;
        }

        if (strlen($newPassword) < self::PASSWORD_MIN_LENGTH || !$this->isPasswordStrong($newPassword)) {
            return false;
        }

        $hashedPassword = $this->hashPassword($newPassword);
        $role = (string) ($user['role'] ?? '');
        $isDealer = $this->isDealerRole($role);
        $table = $isDealer ? 'Dealer' : 'SalesManager';
        $idField = $isDealer ? 'dealerID' : 'salesManagerID';

        return $this->db->query("UPDATE $table SET password = ? WHERE $idField = ?", [$hashedPassword, $userId]) !== false;
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    public function getCurrentUser(): ?array
    {
        return $this->session->getUser();
    }

    /**
     * Identity comes from the server session or a bearer token, never from a client cookie.
     *
     * @return array<string, mixed>
     */
    public function requireCurrentUser(): array
    {
        $user = $this->getCurrentUser();
        if (is_array($user) && trim((string) ($user['id'] ?? '')) !== '') {
            return $user;
        }

        require_once __DIR__ . '/Auth.php';
        $auth = new Auth();
        $token = $auth->getBearerToken();
        $record = is_string($token) && $token !== '' ? $auth->validateToken($token) : null;
        if (!is_array($record)) {
            throw new RuntimeException('Authentication required', 401);
        }

        $sessionUser = [
            'id' => (string) ($record['UserID'] ?? ''),
            'username' => (string) ($record['UserID'] ?? ''),
            'role' => (string) ($record['UserRole'] ?? ''),
        ];
        if ($sessionUser['id'] === '') {
            throw new RuntimeException('Authentication required', 401);
        }

        $this->session->setUser($sessionUser);

        return $sessionUser;
    }

    /**
     * @return array<string, mixed>
     */
    public function requireRole(string $requiredRole): array
    {
        $user = $this->requireCurrentUser();
        if (!RoleName::is((string) ($user['role'] ?? ''), $requiredRole)) {
            throw new RuntimeException('Forbidden', 403);
        }

        return $user;
    }

    public function isLoggedIn(): bool
    {
        return $this->session->isLoggedIn();
    }

    private function updateSessionUser(string $userId): void
    {
        $user = $this->getUser($userId);

        if ($user === null) {
            return;
        }

        $this->session->setUser([
            'id' => $user['UserID'] ?? '',
            'username' => $user['UserID'] ?? '',
            'role' => $user['UserRole'] ?? '',
            'companyName' => $user['CompanyName'] ?? '',
            'contactName' => $user['contactName'] ?? '',
            'surname' => $user['Surname'] ?? '',
        ]);
    }

    public function getUser(string $userId): ?array
    {
        $userId = trim($userId);

        if ($userId === '') {
            return null;
        }

        $user = $this->db->query("SELECT * FROM UserRoles WHERE UserID = ?", [$userId]);
        return !empty($user) ? $user[0] : null;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    private function validateInput(string $input): bool
    {
        return ValidationHelper::isBoundedText($input, 255);
    }

    private function rememberUsername(string $username): void
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        setcookie('username', $username, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function isPasswordStrong(string $password): bool
    {
        if (strlen($password) < self::PASSWORD_MIN_LENGTH || strlen($password) > 128) {
            return false;
        }

        return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/', $password);
    }

    public function isUserLocked(string $username): bool
    {
        $username = trim($username);

        if ($username === '') {
            return false;
        }

        $lockInfo = $this->db->query(
            "SELECT COUNT(*) as attempts, MAX(attempt_time) as last_attempt
             FROM login_attempts
             WHERE username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND) AND success = 0",
            [$username, self::LOCKOUT_TIME]
        );

        $attempts = (int) ($lockInfo[0]['attempts'] ?? 0);
        return $attempts >= self::MAX_LOGIN_ATTEMPTS;
    }

    public function getRoleByUserId(mixed $UserID): ?string
    {
        if (!is_scalar($UserID) || trim((string) $UserID) === '') {
            return null;
        }

        $user = $this->getUser(trim((string) $UserID));

        return isset($user['UserRole']) ? (string) $user['UserRole'] : null;
    }

    private function isDealerRole(string $role): bool
    {
        return RoleName::is($role, 'dealer');
    }

    private function getClientIpAddress(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
