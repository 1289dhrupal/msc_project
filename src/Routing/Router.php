<?php

declare(strict_types=1);

namespace MscProject\Routing;

use ErrorException;
use Exception;
use MscProject\Models\ErrorResponse;
use MscProject\Models\Response;

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

    private static function add(string $requestType, string $pattern, string $controller, string $method, array $middleware = []): void
    {
        self::$routes[$requestType][$pattern] = ['class' => $controller, 'method' => $method, 'middleware' => $middleware];
    }

    public static function dispatch(string $requestMethod, string $requestUri): void
    {
        try {
            $route = self::matchRoute($requestUri, self::$routes[$requestMethod] ?? []);

            if ($route) {
                // Check for middleware and authenticate if necessary
                foreach ($route['middleware'] as $middleware) {
                    if (!Orchestrator::getInstance()->get($middleware)->execute()) {
                        exit();
                    }
                }

                $response = self::handleRequest($route);
            } else {
                throw new ErrorException('Route not found', severity: E_USER_ERROR);
            }
        } catch (\ErrorException $e) {
            $response = new ErrorResponse($e->getMessage(), 'Internal Server Error', headers: ["HTTP/1.0 500 Internal Server Error"]);
        } catch (\Exception $e) {
            $response = new ErrorResponse($e->getMessage(), 'Internal Server Error', headers: ["HTTP/1.0 500 Internal Server Error"]);
        } finally {
            $response->send();
        }
    }

    private static function matchRoute(string $requestUri, array $routes): ?array
    {
        foreach ($routes as $pattern => $routeInfo) {
            if (preg_match($pattern, $requestUri, $params)) {
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
            throw new ErrorException('Implementation not found', severity: E_USER_ERROR);
        }

        return call_user_func_array([$instance, $route['method']], $route['params']);
    }
}
