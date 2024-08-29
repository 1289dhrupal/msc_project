<?php

declare(strict_types=1);

namespace MscProject\Routing;

use MscProject\Models\Response\ErrorResponse;
use MscProject\Models\Response\Response;

class Router
{
    private static array $routes = [];

    private function __construct()
    {
        // Private constructor to prevent instantiation
    }

    public static function post(string $pattern, string $controller, string $method, array $middleware = []): void
    {
        self::add('POST', $pattern, $controller, $method, $middleware);
    }

    public static function get(string $pattern, string $controller, string $method, array $middleware = []): void
    {
        self::add('GET', $pattern, $controller, $method, $middleware);
    }

    public static function delete(string $pattern, string $controller, string $method, array $middleware = []): void
    {
        self::add('DELETE', $pattern, $controller, $method, $middleware);
    }

    private static function add(string $requestType, string $pattern, string $controller, string $method, array $middleware = []): void
    {
        self::$routes[$requestType][$pattern] = ['class' => $controller, 'method' => $method, 'middleware' => $middleware];
    }

    public static function dispatch(string $requestMethod, string $requestUri): void
    {
        try {
            $route = self::matchRoute($requestUri, self::$routes[$requestMethod] ?? []);

            if (!$route) {
                throw new \ErrorException('Route not found', 404, E_USER_ERROR);
            }

            // Check for middleware and authenticate if necessary
            foreach ($route['middleware'] as $middleware) {
                Orchestrator::getInstance()->get($middleware)->execute();
            }

            $response = self::handleRequest($route);
        } catch (\ErrorException $e) {
            $response = new ErrorResponse($e->getMessage(), 'Internal Server Error: ' . $e->getTraceAsString(), $e->getCode());
        } catch (\Exception $e) {
            $response = new ErrorResponse('Something went wrong', 'Internal Server Error: ' . $e->getTraceAsString(), 500);
        } finally {
            $response->send();
        }
    }

    private static function matchRoute(string $requestUri, array $routes): ?array
    {
        // Strip query string (?foo=bar) before matching
        $requestUri = strtok($requestUri, '?');

        foreach ($routes as $pattern => $routeInfo) {
            // Replace placeholders with regex patterns
            $pattern = preg_replace('/\$\{[^\}]+\}/', '([^/]+)', $pattern);

            if (preg_match("#^$pattern$#", $requestUri, $params)) {
                array_shift($params);
                $routeInfo['params'] = $params;
                return $routeInfo;
            }
        }

        return null;
    }

    private static function handleRequest(array $route): Response
    {
        $instance = Orchestrator::getInstance()->get($route['class']);

        if (!$instance or !method_exists($instance, $route['method'])) {
            throw new \ErrorException('Implementation not found', 500, severity: E_USER_ERROR);
        }

        return call_user_func_array([$instance, $route['method']], $route['params']);
    }
}
