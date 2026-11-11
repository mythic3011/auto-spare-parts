<?php

declare(strict_types=1);

class Shipping
{
    private const DEFAULT_TIMEOUT_SECONDS = 5;
    private const ALLOWED_MODES = ['weight', 'quantity'];

    private string $apiUrl;
    private int $timeoutSeconds;

    public function __construct(?string $apiUrl = null, int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS)
    {
        $origin = getenv('SHIPPING_SERVICE_URL');
        $this->apiUrl = $apiUrl ?? (is_string($origin) && $origin !== '' ? rtrim($origin, '/') . '/ship_cost_api' : '');
        $this->timeoutSeconds = max(1, $timeoutSeconds);
    }

    public function calculateShippingCost(string $mode, int|float $value, ?float $fallbackCost = null): float
    {
        $mode = $this->normalizeMode($mode);
        $value = $this->normalizePositiveNumber($value, $mode);

        try {
            return $this->requestShippingCost($mode, $value);
        } catch (Throwable $e) {
            error_log('Shipping service request failed');

            if ($fallbackCost !== null && $fallbackCost >= 0) {
                return $fallbackCost;
            }

            throw new RuntimeException('Shipping cost service is unavailable', 503, $e);
        }
    }

    private function requestShippingCost(string $mode, int|float $value): float
    {
        if (!filter_var($this->apiUrl, FILTER_VALIDATE_URL)
            || !in_array(parse_url($this->apiUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new RuntimeException('Shipping service URL is not configured');
        }
        return $this->requestShippingCostByJson($mode, $value);
    }

    private function requestShippingCostByJson(string $mode, int|float $value): float
    {
        $payload = json_encode([
            'mode' => $mode,
            'value' => $value,
        ], JSON_THROW_ON_ERROR);

        return $this->executeRequest($this->apiUrl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ]);
    }

    /**
     * @param array<int, mixed> $curlOptions
     */
    private function executeRequest(string $url, array $curlOptions): float
    {
        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException('Failed to initialize shipping request');
        }

        try {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
                CURLOPT_FAILONERROR => false,
            ] + $curlOptions);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                throw new RuntimeException('Failed to call shipping cost API: ' . ($curlError ?: 'Unknown cURL error'));
            }

            if ($httpCode < 200 || $httpCode >= 300) {
                throw new RuntimeException('Shipping cost API returned HTTP ' . $httpCode . ': ' . substr((string) $response, 0, 200));
            }

            return $this->parseShippingResponse((string) $response);
        } finally {
            curl_close($ch);
        }
    }

    private function normalizeMode(string $mode): string
    {
        $mode = strtolower(trim($mode));

        if (!in_array($mode, self::ALLOWED_MODES, true)) {
            throw new InvalidArgumentException('Unsupported shipping mode');
        }

        return $mode;
    }

    private function parseShippingResponse(string $response): float
    {
        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
            throw new RuntimeException('Invalid JSON response from shipping cost API');
        }

        if (($result['result'] ?? '') === 'rejected') {
            throw new RuntimeException('Shipping cost calculation rejected: ' . ($result['reason'] ?? 'Unknown reason'));
        }

        if (($result['result'] ?? null) !== 'accepted' || !isset($result['cost'])
            || !is_numeric($result['cost']) || !is_finite((float) $result['cost']) || (float) $result['cost'] < 0) {
            throw new RuntimeException('Shipping cost API response is missing numeric cost');
        }

        return (float) $result['cost'];
    }

    private function normalizePositiveNumber(int|float $value, string $mode): int|float
    {
        if (!is_finite((float) $value) || $value <= 0) {
            throw new InvalidArgumentException($mode . ' must be a positive numeric value');
        }

        if ($mode === 'quantity' && floor((float) $value) !== (float) $value) {
            throw new InvalidArgumentException('quantity must be a whole number');
        }

        return $mode === 'quantity' ? (int) $value : round((float) $value, 3);
    }
}