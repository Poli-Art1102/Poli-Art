<?php
declare(strict_types=1);

final class Router
{
    /** @var array<string, callable> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $this->routes[$method . ' ' . $this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $handler = $this->routes[$method . ' ' . $this->normalizePath($path)] ?? null;
        if (!$handler) { http_response_code(404); echo 'Ruta no encontrada'; return; }
        $handler();
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
