<?php

declare(strict_types=1);

final class RouteRegistry
{
    /**
     * @var array<string, array{
     *   methods: array<int, string>,
     *   handler: callable,
     *   auth: bool,
     *   role: string|null
     * }>
     */
    private array $routes = [];

    /**
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * @param array<int, string> $methods
     */
    public function add(string $key, array $methods, callable $handler, bool $authRequired = true, ?string $requiredRole = null): void
    {
        $this->routes[$key] = [
            'methods' => array_values(array_unique(array_map(static fn (string $method): string => strtoupper($method), $methods))),
            'handler' => $handler,
            'auth' => $authRequired,
            'role' => $requiredRole,
        ];
    }

    public function alias(string $legacyAction, string $routeKey): void
    {
        $this->aliases[$legacyAction] = $routeKey;
    }

    public function resolve(string $routeOrAlias): ?string
    {
        if (isset($this->routes[$routeOrAlias])) {
            return $routeOrAlias;
        }

        return $this->aliases[$routeOrAlias] ?? null;
    }

    /**
     * @return array{
     *   methods: array<int, string>,
     *   handler: callable,
     *   auth: bool,
     *   role: string|null
     * }|null
     */
    public function routeDefinition(string $routeOrAlias): ?array
    {
        $resolved = $this->resolve($routeOrAlias);
        if ($resolved === null) {
            return null;
        }

        return $this->routes[$resolved] ?? null;
    }

    public function dispatch(string $routeOrAlias, string $method, mixed ...$args): mixed
    {
        $route = $this->routeDefinition($routeOrAlias);
        if ($route === null) {
            throw new RuntimeException('Route not found', 404);
        }

        $normalizedMethod = strtoupper(trim($method));
        if (!in_array($normalizedMethod, $route['methods'], true)) {
            throw new RuntimeException('Method not allowed', 405);
        }

        return ($route['handler'])(...$args);
    }
}
