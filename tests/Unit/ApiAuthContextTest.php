<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApiAuthContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_SESSION = [];
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    #[Test]
    public function it_requires_auth_token(): void
    {
        $context = new \ApiAuthContext();
        $request = new \ApiRequest();
        $auth = new class extends \Auth {
            public function __construct()
            {
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Authentication required');
        $context->requireUser($auth, $request);
    }

    #[Test]
    public function it_normalizes_authenticated_user(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid-token';

        $context = new \ApiAuthContext();
        $request = new \ApiRequest();
        $auth = new class extends \Auth {
            public function __construct()
            {
            }

            public function validateToken($token)
            {
                if ($token === 'valid-token') {
                    return ['UserID' => 'dealer1', 'UserRole' => 'Dealer'];
                }
                return null;
            }
        };

        $user = $context->requireUser($auth, $request);
        $this->assertSame('dealer1', $user['UserID']);
        $this->assertSame('dealer1', $user['id']);
        $this->assertSame('dealer1', $user['username']);
        $this->assertSame('Dealer', $user['role']);
    }

    #[Test]
    public function it_enforces_required_role(): void
    {
        $context = new \ApiAuthContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Forbidden');
        $context->requireRole(['role' => 'Dealer'], 'Sales Manager');
    }
}
