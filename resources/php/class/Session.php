<?php

class Session
{
    /** @var array<string, mixed> */
    private array $sessionData;
    private string $token = '';
    private int $cookieLifetime;
    private ?string $cookieDomain;
    private string $cookiePath;
    private bool $cookieSecure;
    private string $cookieSameSite;

    public function __construct(
        int     $cookieLifetime = 3600,
        string  $cookiePath = '/',
        ?string $cookieDomain = null,
        ?bool   $cookieSecure = null,
        string  $cookieSameSite = 'Lax'
    )
    {
        $this->cookieLifetime = $cookieLifetime;
        $this->cookiePath = $cookiePath;
        $this->cookieDomain = $cookieDomain;
        $this->cookieSecure = $cookieSecure ?? $this->isSecureRequest();
        $this->cookieSameSite = $cookieSameSite;

        if (session_status() === PHP_SESSION_NONE) {
            $this->startSecureSession();
        }
        $this->sessionData = &$_SESSION;
        $this->token = $this->get('token') ?? $this->regenerateToken();
    }

    public static function begin(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        new self();
    }

    private function startSecureSession(): void
    {
        if (!headers_sent()) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', $this->cookieSecure ? '1' : '0');
            ini_set('session.cookie_samesite', $this->cookieSameSite);
            ini_set('session.gc_maxlifetime', $this->cookieLifetime);

            $options = $this->buildCookieOptions(time() + $this->cookieLifetime);
            unset($options['expires']);
            $options['lifetime'] = $this->cookieLifetime;
            session_set_cookie_params($options);
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (session_status() === PHP_SESSION_ACTIVE && !$this->validateSession()) {
            session_regenerate_id(true);
            $this->set('created', time());
            $this->set('last_activity', time());
        } elseif (session_status() !== PHP_SESSION_ACTIVE) {
            $_SESSION['created'] = $_SESSION['created'] ?? time();
            $_SESSION['last_activity'] = $_SESSION['last_activity'] ?? time();
        }
    }

    private function validateSession(): bool
    {
        $now = time();

        if (!isset($_SESSION['created']) || !is_int($_SESSION['created'])) {
            return false;
        }

        if ($now - $_SESSION['created'] > $this->cookieLifetime) {
            return false;
        }

        if (isset($_SESSION['last_activity']) && is_int($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > 300)) {
            return false;
        }

        $_SESSION['last_activity'] = $now;
        return true;
    }

    public function set(string $key, mixed $value): void
    {
        $key = trim($key);

        if ($key === '' || $value === null) {
            throw new InvalidArgumentException('Invalid key or value');
        }

        $this->sessionData[$key] = $value;
        $_SESSION[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->sessionData[$key] ?? $_SESSION[$key] ?? null;
    }

    public function remove(string $key): void
    {
        unset($this->sessionData[$key], $_SESSION[$key]);
    }

    public function setUser(array $user): void
    {
        $this->set('user', $user);
    }

    public function getUser(): ?array
    {
        $user = $this->get('user');

        return is_array($user) ? $user : null;
    }

    public function unsetUser(): void
    {
        $this->remove('user');
    }

    public function setCookie(
        string $name,
        string $value,
        int $expire,
        string $path = '/',
        ?string $domain = null,
        ?bool $secure = null,
        bool $httponly = true
    ): void {
        $name = trim($name);

        if ($name === '' || headers_sent()) {
            return;
        }

        setcookie($name, $value, $this->buildCookieOptions($expire, $path, $domain, $secure, $httponly));

        if ($expire <= time()) {
            unset($_COOKIE[$name]);
        } else {
            $_COOKIE[$name] = $value;
        }
    }

    public function getCookie(string $name): ?string
    {
        $value = $_COOKIE[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    public function deleteCookie(string $name, string $path = '/', ?string $domain = null): void
    {
        $this->setCookie($name, '', time() - 3600, $path, $domain);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function regenerateToken(): string
    {
        $newToken = bin2hex(random_bytes(32));
        $this->set('token', $newToken);
        $this->token = $newToken;
        return $newToken;
    }

    public function destroy(): void
    {
        $_SESSION = [];
        $this->sessionData = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            $this->deleteCookie(session_name(), $params['path'] ?? '/', $params['domain'] ?? null);
        }

        foreach (['user', 'token', 'username', 'role', 'id'] as $cookieName) {
            $this->deleteCookie($cookieName);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function isLoggedIn(): bool
    {
        return is_array($this->get('user'));
    }

    public function validateToken(string $token): bool
    {
        return $token !== '' && $this->token !== '' && hash_equals($this->token, $token);
    }

    public function getSessionData(): array
    {
        return $this->sessionData;
    }

    public function setSessionData(array $sessionData): void
    {
        $this->sessionData = $sessionData;
        $_SESSION = $sessionData;
    }

    public function setToken(string $token): void
    {
        if ($token === '') {
            throw new InvalidArgumentException('Token cannot be empty');
        }

        $this->token = $token;
        $this->set('token', $token);
    }

    public function getCookieLifetime(): int
    {
        return $this->cookieLifetime;
    }

    public function setCookieLifetime(int $cookieLifetime): void
    {
        $this->cookieLifetime = $cookieLifetime;
    }

    public function getCookieDomain(): ?string
    {
        return $this->cookieDomain;
    }

    public function setCookieDomain(?string $cookieDomain): void
    {
        $this->cookieDomain = $cookieDomain;
    }

    public function getCookiePath(): string
    {
        return $this->cookiePath;
    }

    public function setCookiePath(string $cookiePath): void
    {
        $this->cookiePath = $cookiePath;
    }

    public function getCookieSecure(): bool
    {
        return $this->cookieSecure;
    }

    public function setCookieSecure(bool $cookieSecure): void
    {
        $this->cookieSecure = $cookieSecure;
    }

    public function getCookieSameSite(): string
    {
        return $this->cookieSameSite;
    }

    public function setCookieSameSite(string $cookieSameSite): void
    {
        $this->cookieSameSite = $cookieSameSite;
    }

    private function buildCookieOptions(
        int $expire,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        bool $httponly = true
    ): array {
        $options = [
            'expires' => $expire,
            'path' => $path ?? $this->cookiePath,
            'secure' => $secure ?? $this->cookieSecure,
            'httponly' => $httponly,
            'samesite' => $this->cookieSameSite,
        ];

        $cookieDomain = $domain ?? $this->cookieDomain;

        if ($cookieDomain !== null && $cookieDomain !== '') {
            $options['domain'] = $cookieDomain;
        }

        return $options;
    }

    private function isSecureRequest(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    }
}
