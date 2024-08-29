<?php

require '../vendor/autoload.php';

use Dotenv\Dotenv;
use MscProject\Controllers\DashboardController;
use MscProject\Routing\Router;
use MscProject\Routing\Orchestrator;
use MscProject\Middleware\AuthMiddleware;
use MscProject\Controllers\UserController;
use MscProject\Controllers\GitTokenController;
use MscProject\Controllers\GitController;
use MscProject\Controllers\WebHookController;
use MscProject\Models\Response\ErrorResponse;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Allow from any origin
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    exit(0);
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Credentials: true");

// Create the Orchestrator with the configuration
Orchestrator::getInstance();

Router::get('/verify', UserController::class, 'verify');
Router::post('/register', UserController::class, 'register');
Router::post('/login', UserController::class, 'login');
Router::post('/logout', UserController::class, 'logout', [AuthMiddleware::class]);

Router::post('/git-token/store', GitTokenController::class, 'store', [AuthMiddleware::class]);
Router::get('/git-token/list', GitTokenController::class, 'list', [AuthMiddleware::class]);
Router::delete('/git-token/${tokenId}', GitTokenController::class, 'delete', [AuthMiddleware::class]);
Router::post('/git-token/${tokenId}/toggle', GitTokenController::class, 'toggle', [AuthMiddleware::class]);

Router::get('/git/repositories/list', GitController::class, 'listRepositories', [AuthMiddleware::class]);
Router::get('/git/repositories/${repositoryId}/commits', GitController::class, 'listCommits', [AuthMiddleware::class]);
Router::get('/git/commits', GitController::class, 'listCommits', [AuthMiddleware::class]);
Router::post('/git/repositories/${repositoryId}/toggle', GitController::class, 'toggleRepository', [AuthMiddleware::class]);
Router::delete('/git/repositories/${repositoryId}', GitController::class, 'deleteRepository', [AuthMiddleware::class]);
Router::get('/git/repositories/${repositoryId}/stats', GitController::class, 'getStats', [AuthMiddleware::class]);

Router::get('/dashboard/overallStats', DashboardController::class, 'overallStats', [AuthMiddleware::class]);

Router::post($_ENV['GITHUB_WEBHOOK_RESPONSE_URL'], WebHookController::class, 'handleGitHubWebhook');
Router::post($_ENV['GITLAB_WEBHOOK_RESPONSE_URL'], WebHookController::class, 'handleGitLabWebhook');

try {
    // Dispatch the request (example usage)
    Router::dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (\Throwable $e) {
    $response = new ErrorResponse($e->getMessage(), $e->getTraceAsString(), 500);
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
