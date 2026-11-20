<?php

namespace App;

use App\Middleware\MiddlewareInterface;

class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];

    public function addGlobalMiddleware(MiddlewareInterface $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function any(string $path, callable $handler, array $middleware = []): void
    {
        $this->addRoute('*', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, callable $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(?string $method = null, ?string $uri = null): mixed
    {
        $method ??= $_SERVER['REQUEST_METHOD'];
        $uri ??= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        $page = $_GET['page'] ?? 'home';

        foreach ($this->routes as $route) {
            if ($route['method'] !== '*' && $route['method'] !== $method) {
                continue;
            }

            $routePath = $route['path'];
            if ($routePath === $page || $routePath === '*') {
                $middlewareChain = array_merge($this->globalMiddleware, $route['middleware']);
                $handler = $route['handler'];

                $pipeline = array_reduce(
                    array_reverse($middlewareChain),
                    fn(callable $next, MiddlewareInterface $m) => fn() => $m->handle($next),
                    $handler
                );

                try {
                    return $pipeline();
                } catch (HttpException $e) {
                    $e->send();
                }
            }
        }

        http_response_code(404);
        require __DIR__ . '/../scripts/404.php';
        return null;
    }
}
