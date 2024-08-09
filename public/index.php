<?php

require '../vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Routing\Router;
use MscProject\Routing\Orchestrator;
use MscProject\Middleware\AuthMiddleware;
use MscProject\Controllers\UserController;
use MscProject\Controllers\GitTokenController;
use MscProject\Models\Response\ErrorResponse;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Allow from any origin
header("Access-Control-Allow-Origin: *");

// Allow specific HTTP methods (e.g., POST)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Allow specific headers
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Create the Orchestrator with the configuration
Orchestrator::getInstance();

Router::post('#^/register$#', UserController::class, 'register');
Router::get('#^/verify$#', UserController::class, 'verify');
Router::post('#^/login$#', UserController::class, 'login');
Router::post('#^/logout$#', UserController::class, 'logout', [AuthMiddleware::class]);
Router::post('#^/git-token/store$#', GitTokenController::class, 'store', [AuthMiddleware::class]);

try {
    // Dispatch the request (example usage)
    Router::dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (\Throwable $e) {
    $response = new ErrorResponse('Something went wrong', 'Internal Server Error', 500);
    $response->send();
}

/*
GET - The GET method requests a representation of the specified resource. Requests using GET should only retrieve data and should have no other effect on the data.
POST - The POST method is used to submit an entity to the specified resource, often causing a change in state or side effects on the server.
PUT - The PUT method replaces all current representations of the target resource with the request payload.
DELETE - The DELETE method deletes the specified resource.
HEAD - The HEAD method asks for a response identical to that of a GET request, but without the response body.
OPTIONS - The OPTIONS method is used to describe the communication options for the target resource.
PATCH - The PATCH method is used to apply partial modifications to a resource.
CONNECT - The CONNECT method establishes a tunnel to the server identified by the target resource.
TRACE - The TRACE method performs a message loop-back test along the path to the target resource.
 */