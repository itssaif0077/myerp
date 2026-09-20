<?php
declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Enterprise RESTful Router & Middleware Pipeline
 * Matches incoming HTTP routes and executes middleware pipelines and controllers.
 */
class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function use(string $middlewareClass): void
    {
        $this->globalMiddleware[] = $middlewareClass;
    }

    public function get(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, array|Closure $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array|Closure $handler, array $middleware = []): void
    {
        $normalizedPath = '/' . trim($path, '/');
        $this->routes[] = [
            'method'     => $method,
            'path'       => $normalizedPath,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Dispatch the current request
     */
    public function dispatch(Request $request): mixed
    {
        $requestMethod = $request->getMethod();
        $requestUri = $request->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            $routePattern = $this->compileRoutePattern($route['path']);
            if (preg_match($routePattern, $requestUri, $matches)) {
                // Extract route parameters
                $params = array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
                foreach ($params as $paramKey => $paramVal) {
                    $request->setAttribute($paramKey, $paramVal);
                }

                // Compile combined middleware pipeline (globals + route specific)
                $pipeline = array_merge($this->globalMiddleware, $route['middleware']);

                return $this->runPipeline($request, $pipeline, function (Request $req) use ($route, $params) {
                    return $this->invokeHandler($route['handler'], $req, $params);
                });
            }
        }

        Response::notFound("Route [{$requestMethod} {$requestUri}] not found.");
        return null;
    }

    private function compileRoutePattern(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function runPipeline(Request $request, array $middlewares, Closure $destination): mixed
    {
        $next = $destination;

        while ($middlewareClass = array_pop($middlewares)) {
            $middlewareInstance = $this->container->make($middlewareClass);
            $next = function (Request $req) use ($middlewareInstance, $next) {
                return $middlewareInstance->handle($req, $next);
            };
        }

        return $next($request);
    }

    private function invokeHandler(array|Closure $handler, Request $request, array $params): mixed
    {
        if ($handler instanceof Closure) {
            return $handler($request, ...array_values($params));
        }

        [$controllerClass, $method] = $handler;
        $controller = $this->container->make($controllerClass);

        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Method [{$method}] not found on controller [{$controllerClass}].");
        }

        return $controller->$method($request, ...array_values($params));
    }
}
