<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ResponseHandler;

class ResponseHandlerTest extends TestCase
{
    /**
     * Capture output from ResponseHandler (which calls exit internally).
     * We run it in a child process and capture stdout.
     */
    private function capture(string $call): array
    {
        $script = sprintf(
            '<?php
            putenv("DB_DOCKER_NAME=projectdb_test");
            putenv("DB_NAME=projectdb_test");
            require_once %s;
            require_once %s;
            %s;',
            var_export(__DIR__ . '/../../resources/php/class/config.php', true),
            var_export(__DIR__ . '/../../resources/php/class/ResponseHandler.php', true),
            $call
        );

        $tmp = tempnam(sys_get_temp_dir(), 'rh_test_') . '.php';
        file_put_contents($tmp, $script);

        $output = shell_exec('php ' . escapeshellarg($tmp) . ' 2>/dev/null');
        unlink($tmp);

        $decoded = json_decode((string)$output, true);
        return $decoded ?? [];
    }

    #[Test]
    public function it_returns_success_response_with_200(): void
    {
        $result = $this->capture("ResponseHandler::success(['data' => 'test'], 'OK')");

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('OK', $result['message']);
    }

    #[Test]
    public function it_returns_created_response_with_201(): void
    {
        $result = $this->capture("ResponseHandler::created(['id' => '1'], 'Created')");

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function it_returns_error_response_with_400(): void
    {
        $result = $this->capture("ResponseHandler::error('Bad request', 400)");

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Bad request', $result['message']);
    }

    #[Test]
    public function it_returns_unauthorized_with_401(): void
    {
        $result = $this->capture("ResponseHandler::unauthorized('Invalid token')");

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Invalid token', $result['message']);
    }

    #[Test]
    public function it_returns_not_found_with_404(): void
    {
        $result = $this->capture("ResponseHandler::notFound('Not found')");

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Not found', $result['message']);
    }

    #[Test]
    public function it_returns_validation_error_with_422(): void
    {
        $result = $this->capture(
            "ResponseHandler::validationError(['email' => ['Email required']])"
        );

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Validation failed', $result['message']);
    }

    #[Test]
    public function it_includes_timestamp_in_response(): void
    {
        $result = $this->capture("ResponseHandler::success([], 'OK')");

        $this->assertArrayHasKey('timestamp', $result);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $result['timestamp']);
    }

    #[Test]
    public function it_formats_json_correctly(): void
    {
        $result = $this->capture("ResponseHandler::success(['key' => 'value'], 'Test')");

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('data', $result);
    }

    #[Test]
    public function it_includes_error_details_in_error_response(): void
    {
        $result = $this->capture(
            "ResponseHandler::error('Error', 400, ['field' => 'detail'])"
        );

        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('errors', $result['data']);
    }
}
