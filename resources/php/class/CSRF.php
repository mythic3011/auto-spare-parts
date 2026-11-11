<?php

class CSRF {
    private const TOKEN_LENGTH = 32;

    public function generateToken(): string {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH / 2));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public function validateToken(string $token): bool {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        if ($valid) {
            $this->regenerateToken();
        }
        return $valid;
    }

    private function regenerateToken(): void {
        $this->generateToken();
    }
}
