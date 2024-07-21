<?php

require '../vendor/autoload.php';

use MscProject\Controllers\UserController;
use MscProject\Middleware\AuthMiddleware;

$controller = new UserController();
$authMiddleware = new AuthMiddleware();

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');

$publicRoutes = [
    'POST' => [
        '#^/register$#' => ['UserController', 'register'],
        '#^/login$#' => ['UserController', 'login']
    ]
];

$protectedRoutes = [
    'POST' => [
        '#^/logout$#' => ['UserController', 'logout']
    ],
    'GET' => [
        '#^/authenticate$#' => ['UserController', 'authenticate']
    ]
];

function matchRoute($requestUri, $routes)
{
    foreach ($routes as $pattern => $callback) {
        if (preg_match($pattern, $requestUri, $params)) {
            array_shift($params);
            return [$callback, $params];
        }
    }

    return null;
}

function handleRequest($route, $params)
{
    [$callback, $params] = $route;
    [$class, $method] = $callback;
    $fullClassName = "MscProject\\Controllers\\$class";
    if (class_exists($fullClassName) && method_exists($fullClassName, $method)) {
        $instance = new $fullClassName();
        call_user_func_array([$instance, $method], $params);
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        echo "500 Internal Server Error";
    }
}

$route = null;
$params = [];

if (isset($publicRoutes[$requestMethod])) {
    $route = matchRoute($requestUri, $publicRoutes[$requestMethod]);
}

if (!$route && isset($protectedRoutes[$requestMethod])) {
    $route = matchRoute($requestUri, $protectedRoutes[$requestMethod]);
    if ($route) {
        $authMiddleware->authenticate();
    }
}

if ($route) {
    handleRequest($route, $params);
} else {
    header("HTTP/1.0 404 Not Found");
    echo "404 Not Found";
}
