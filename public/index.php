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

// CORS Handling
handleCORS();

function handleCORS(): void
{
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
}

// Initialize Orchestrator
Orchestrator::getInstance();

// Define Routes
Router::get('/verify', UserController::class, 'verify');
Router::post('/register', UserController::class, 'register');
Router::post('/login', UserController::class, 'login');
Router::post('/logout', UserController::class, 'logout', [AuthMiddleware::class]);
Router::get('/user', UserController::class, 'getUser', [AuthMiddleware::class]);
Router::post('/user', UserController::class, 'updateUser', [AuthMiddleware::class]);
Router::post('/user/alerts', UserController::class, 'updateAlerts', [AuthMiddleware::class]);

Router::post('/git-token/store', GitTokenController::class, 'store', [AuthMiddleware::class]);
Router::get('/git-token/list', GitTokenController::class, 'list', [AuthMiddleware::class]);
Router::delete('/git-token/${tokenId}', GitTokenController::class, 'delete', [AuthMiddleware::class]);
Router::post('/git-token/${tokenId}/edit', GitTokenController::class, 'edit', [AuthMiddleware::class]);
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

// Request Dispatch and Error Handling
try {
    Router::dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
} catch (Throwable $e) {
    $response = new ErrorResponse($e->getMessage(), $e->getTraceAsString(), 500);
    $response->send();
}

/*
HTTP Methods Description:
GET     - Retrieve data from the server.
POST    - Submit data to the server.
PUT     - Replace the current representation of the target resource.
DELETE  - Remove the specified resource.
HEAD    - Same as GET, but without the response body.
OPTIONS - Describe the communication options for the target resource.
PATCH   - Apply partial modifications to a resource.
CONNECT - Establish a tunnel to the server identified by the target resource.
TRACE   - Perform a message loop-back test along the path to the target resource.
 */
