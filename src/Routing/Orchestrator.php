<?php

declare(strict_types=1);

namespace MscProject\Routing;

use MscProject\Controllers\{DashboardController, UserController, GitTokenController, GitController, WebhookController};
use MscProject\Services\{UserService, GitTokenService, GitHubService, ActivityService, GitService, GitLabService};
use MscProject\Repositories\{UserRepository, SessionRepository, ActivityRepository, GitRepository};
use MscProject\Middleware\AuthMiddleware;
use ReflectionClass;
use ErrorException;
use ReflectionException;

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

    public static function get(string $class): object
    {
        if (isset(self::$instances[$class])) {
            return self::$instances[$class];
        }

        $instance = self::resolve($class);
        self::$instances[$class] = $instance;
        return $instance;
    }

    private static function resolve(string $class): object
    {
        try {
            $reflectionClass = new ReflectionClass($class);
            $constructor = $reflectionClass->getConstructor();

            if (is_null($constructor)) {
                return new $class();
            }

            $parameters = $constructor->getParameters();
            $dependencies = array_map(function ($parameter) {
                $type = $parameter->getType();
                if ($type && !$type->isBuiltin()) {
                    return self::get($type->getName());
                }
                throw new ErrorException("Cannot resolve dependency {$parameter->getName()}", 500, E_USER_ERROR);
            }, $parameters);

            return $reflectionClass->newInstanceArgs($dependencies);
        } catch (ReflectionException $e) {
            throw new ErrorException("Unable to resolve class: $class", 500, E_USER_ERROR, previous: $e);
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
        return self::get(WebhookController::class);
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
