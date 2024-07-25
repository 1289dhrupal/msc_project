<?php

declare(strict_types=1);

namespace MscProject\Routing;

use ReflectionClass;
use ReflectionException;
use MscProject\Controllers\UserController;
use MscProject\Controllers\GitTokenController;
use MscProject\Services\UserService;
use MscProject\Services\GitTokenService;
use MscProject\Repositories\UserRepository;
use MscProject\Repositories\SessionRepository;
use MscProject\Repositories\GitTokenRepository;
use MscProject\Middleware\AuthMiddleware;

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

    public static function getGitTokenController(): UserController
    {
        return self::get(GitTokenController::class);
    }

    public static function getUserService(): UserService
    {
        return self::get(UserService::class);
    }

    public static function getGitTokenService(): UserService
    {
        return self::get(GitTokenService::class);
    }

    public static function getUserRepository(): UserRepository
    {
        return self::get(UserRepository::class);
    }
    public static function getGitTokenRepository(): UserRepository
    {
        return self::get(GitTokenRepository::class);
    }

    public static function getSessionRepository(): SessionRepository
    {
        return self::get(SessionRepository::class);
    }

    public static function getAuthMiddleware(): AuthMiddleware
    {
        return self::get(AuthMiddleware::class);
    }
}
