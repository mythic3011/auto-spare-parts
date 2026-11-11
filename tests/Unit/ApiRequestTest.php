<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApiRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    #[Test]
    public function it_normalizes_request_method_to_uppercase(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $request = new \ApiRequest();

        $this->assertSame('POST', $request->method());
    }

    #[Test]
    public function it_reads_action_from_query_first(): void
    {
        $_GET['action'] = 'dealer-list';
        $request = new \ApiRequest();

        $this->assertSame('dealer-list', $request->action());
    }

    #[Test]
    public function it_prefers_authorization_header_for_auth_token(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer header-token';
        $_COOKIE['token'] = 'cookie-token';
        $_SESSION['token'] = 'session-token';
        $request = new \ApiRequest();

        $this->assertSame('header-token', $request->authToken());
    }

    #[Test]
    public function it_falls_back_to_cookie_then_session_for_auth_token(): void
    {
        $_COOKIE['userToken'] = 'cookie-token';
        $_SESSION['token'] = 'session-token';
        $request = new \ApiRequest();

        $this->assertSame('cookie-token', $request->authToken());

        $_COOKIE = [];
        $this->assertSame('session-token', $request->authToken());
    }

    #[Test]
    public function it_returns_empty_token_when_no_sources_exist(): void
    {
        $request = new \ApiRequest();
        $this->assertSame('', $request->authToken());
    }
}
