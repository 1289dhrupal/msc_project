<?php

declare(strict_types=1);

namespace MscProject\Routing;

use MscProject\Controllers\DashboardController;
use ReflectionClass;
use ReflectionException;
use MscProject\Controllers\UserController;
use MscProject\Controllers\GitTokenController;
use MscProject\Controllers\GitController;
use MscProject\Services\UserService;
use MscProject\Controllers\WebhookController;
use MscProject\Services\GitTokenService;
use MscProject\Services\GitHubService;
use MscProject\Services\ActivityService;
use MscProject\Services\GitService;
use MscProject\Repositories\UserRepository;
use MscProject\Repositories\SessionRepository;
use MscProject\Repositories\ActivityRepository;
use MscProject\Repositories\GitRepository;
use MscProject\Middleware\AuthMiddleware;
use MscProject\Services\GitLabService;

class Orchestrator
{
    private static ?Orchestrator $instance = null;
    private static array $instances = [];

    private function __construct()
    {
        // Private constructor to prevent instantiation
    }

    public static function getInstance(): Orchestrator
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function get(string $class)
    {
        // Check if an instance already exists
        if (isset(self::$instances[$class])) {
            return self::$instances[$class];
        }

        // Resolve dependencies recursively
        $instance = self::resolve($class);

        // Store the instance for future use
        self::$instances[$class] = $instance;
        return $instance;
    }

    private static function resolve(string $class)
    {
        try {
            $reflectionClass = new ReflectionClass($class);
            $constructor = $reflectionClass->getConstructor();

            if (is_null($constructor)) {
                return new $class();
            } else {
                $parameters = $constructor->getParameters();
                $dependencies = array_map(function ($parameter) {
                    $type = $parameter->getType();
                    if ($type && !$type->isBuiltin()) {
                        return self::get($type->getName());
                    }
                    throw new \Exception("Cannot resolve dependency {$parameter->getName()}");
                }, $parameters);
                return $reflectionClass->newInstanceArgs($dependencies);
            }
        } catch (ReflectionException $e) {
            throw new \Exception("Unable to resolve class: $class", 0, $e);
        }
    }

    public static function getUserController(): UserController
    {
        return self::get(UserController::class);
    }

    public static function getGitController(): GitController
    {
        return self::get(GitController::class);
    }

    public static function getGitTokenController(): GitTokenController
    {
        return self::get(GitTokenController::class);
    }

    public static function getDashboardController(): DashboardController
    {
        return self::get(DashboardController::class);
    }

    public static function getWebHookController(): WebhookController
    {
        return self::get(WebHookController::class);
    }

    public static function getUserService(): UserService
    {
        return self::get(UserService::class);
    }

    public static function getActivityService(): ActivityService
    {
        return self::get(ActivityService::class);
    }

    public static function getGitTokenService(): GitTokenService
    {
        return self::get(GitTokenService::class);
    }

    public static function getGitHubService(): GitHubService
    {
        return self::get(GitHubService::class);
    }

    public static function getGitLabService(): GitLabService
    {
        return self::get(GitLabService::class);
    }

    public static function getGitService(): GitService
    {
        return self::get(GitService::class);
    }

    public static function getUserRepository(): UserRepository
    {
        return self::get(UserRepository::class);
    }

    public static function getSessionRepository(): SessionRepository
    {
        return self::get(SessionRepository::class);
    }

    public static function getGitRepository(): GitRepository
    {
        return self::get(GitRepository::class);
    }

    public static function getActivityRepository(): ActivityRepository
    {
        return self::get(ActivityRepository::class);
    }

    public static function getAuthMiddleware(): AuthMiddleware
    {
        return self::get(AuthMiddleware::class);
    }
}
