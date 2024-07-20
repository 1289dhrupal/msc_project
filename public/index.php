<?php

require '../vendor/autoload.php';

use MscProject\Controllers\UserController;

$controller = new UserController();

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');

$routes = [
    'POST' => [
        '#^/register$#' => ['UserController', 'register'],
        '#^/login$#' => ['UserController', 'login'],
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

if (isset($routes[$requestMethod])) {
    $route = matchRoute($requestUri, $routes[$requestMethod]);
    if ($route) {
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
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
    }
} else {
    header("HTTP/1.0 405 Method Not Allowed");
    echo "405 Method Not Allowed";
}
