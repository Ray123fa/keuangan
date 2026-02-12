<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<string, callable> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function dispatch(string $method, string $requestUri): void
    {
        $path = $this->normalizePath($requestUri);
        $key = strtoupper($method) . ':' . $path;

        if (!isset($this->routes[$key])) {
            http_response_code(404);
            echo 'Route tidak ditemukan.';
            return;
        }

        call_user_func($this->routes[$key]);
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $normalizedPath = $this->normalizeSimplePath($path);
        $this->routes[strtoupper($method) . ':' . $normalizedPath] = $handler;
    }

    private function normalizePath(string $requestUri): string
    {
        $path = (string) parse_url($requestUri, PHP_URL_PATH);
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        return $this->normalizeSimplePath($path);
    }

    private function normalizeSimplePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
