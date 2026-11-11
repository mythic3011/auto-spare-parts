<?php

class TwoFactorAuth {
    private const TEMP_TOKEN_LENGTH = 32;
    private const TEMP_TOKEN_EXPIRY = 600; // 10 minutes

    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function generateTempToken(string $username): string {
        $token = bin2hex(random_bytes(self::TEMP_TOKEN_LENGTH / 2));
        $expiry = time() + self::TEMP_TOKEN_EXPIRY;

        $this->db->query(
            "INSERT INTO temp_tokens (username, token, expiry) VALUES (?, ?, ?)",
            [$username, $token, date('Y-m-d H:i:s', $expiry)]
        );

        return $token;
    }

    public function verifyTempToken(string $username, string $token): bool {
        $result = $this->db->query(
            "SELECT * FROM temp_tokens WHERE username = ? AND token = ? AND expiry > NOW()",
            [$username, $token]
        );

        if (!empty($result)) {
            $this->db->query("DELETE FROM temp_tokens WHERE username = ?", [$username]);
            return true;
        }

        return false;
    }

    public function verifyCode(string $username, string $code): bool {
        if ($this->verifyTempToken($username, $code)) {
            return true;
        }
        return false;
    }
}
