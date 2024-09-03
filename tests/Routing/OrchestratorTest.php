<?php

use PHPUnit\Framework\TestCase;
use MscProject\Routing\Orchestrator;
use MscProject\Controllers\UserController;
use MscProject\Services\UserService;
use MscProject\Repositories\UserRepository;
use MscProject\Repositories\SessionRepository;

class OrchestratorTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testSingletonInstance()
    {
        $instance1 = Orchestrator::getInstance();
        $instance2 = Orchestrator::getInstance();

        $this->assertSame($instance1, $instance2, "Orchestrator::getInstance() should return the same instance.");
    }

    public function testGetUserControllerResolvesCorrectly()
    {
        $userController = Orchestrator::getInstance()->getUserController();

        // Verify that the returned object is an instance of UserController
        $this->assertInstanceOf(UserController::class, $userController);

        // Verify that UserController has a UserService instance injected
        $userService = $this->getPrivateProperty($userController, 'service');
        $this->assertInstanceOf(UserService::class, $userService);

        // Verify that UserService has a UserRepository instance injected
        $userRepository = $this->getPrivateProperty($userService, 'userRepository');
        $this->assertInstanceOf(UserRepository::class, $userRepository);

        // Verify that UserService has a SessionRepository instance injected
        $sessionRepository = $this->getPrivateProperty($userService, 'sessionRepository');
        $this->assertInstanceOf(SessionRepository::class, $sessionRepository);
    }

    private function getPrivateProperty($object, $propertyName)
    {
        $reflector = new \ReflectionClass($object);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    public function testInstanceCaching()
    {
        $orchestrator = Orchestrator::getInstance();

        // Get UserService instance twice and ensure they are the same
        $userService1 = $orchestrator->getUserService();
        $userService2 = $orchestrator->getUserService();

        $this->assertSame($userService1, $userService2, "Orchestrator::get() should return the same instance from the cache.");
    }

    public function testResolveExceptionForUnknownClass()
    {
        $this->expectException(ErrorException::class);
        Orchestrator::getInstance()->get('NonExistentClass');
    }
}
