<?php

use PHPUnit\Framework\TestCase;
use MscProject\Routing\Router;
use MscProject\Controllers\UserController;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the Router's static routes array before each test
        $this->resetRouter();
    }

    protected function tearDown(): void
    {
        // Reset the Router's static routes array after each test
        $this->resetRouter();
    }

    private function resetRouter(): void
    {
        $reflector = new ReflectionClass(Router::class);
        $routes = $reflector->getProperty('routes');
        $routes->setAccessible(true);
        $routes->setValue(null, []);  // Pass null as the object for static properties
    }

    public function testMatchExactRoute()
    {
        Router::get('/user/profile', UserController::class, 'getUser');

        $routes = $this->getPrivateProperty(Router::class, 'routes');
        $matchedRoute = $this->callMatchRoute('/user/profile', $routes['GET']);

        $this->assertNotNull($matchedRoute);
        $this->assertEquals(UserController::class, $matchedRoute['class']);
        $this->assertEquals('getUser', $matchedRoute['method']);
    }

    public function testMatchRouteWithParameters()
    {
        Router::get('/user/${id}', UserController::class, 'getUser');

        $routes = $this->getPrivateProperty(Router::class, 'routes');
        $matchedRoute = $this->callMatchRoute('/user/123', $routes['GET']);

        $this->assertNotNull($matchedRoute);
        $this->assertEquals(UserController::class, $matchedRoute['class']);
        $this->assertEquals('getUser', $matchedRoute['method']);
        $this->assertEquals(['123'], $matchedRoute['params']);
    }

    public function testMatchRouteWithMultipleParameters()
    {
        Router::get('/user/${id}/posts/${postId}', UserController::class, 'getUserPosts');

        $routes = $this->getPrivateProperty(Router::class, 'routes');
        $matchedRoute = $this->callMatchRoute('/user/123/posts/456', $routes['GET']);

        $this->assertNotNull($matchedRoute);
        $this->assertEquals(UserController::class, $matchedRoute['class']);
        $this->assertEquals('getUserPosts', $matchedRoute['method']);
        $this->assertEquals(['123', '456'], $matchedRoute['params']);
    }

    public function testNoMatchForIncorrectRoute()
    {
        Router::get('/user/profile', UserController::class, 'getUser');

        $routes = $this->getPrivateProperty(Router::class, 'routes');
        $matchedRoute = $this->callMatchRoute('/user/unknown', $routes['GET']);

        $this->assertNull($matchedRoute);
    }

    private function getPrivateProperty($class, $propertyName)
    {
        $reflector = new ReflectionClass($class);
        $property = $reflector->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue(null);  // For static properties, pass null
    }

    private function callMatchRoute($requestUri, $routes)
    {
        $reflector = new ReflectionClass(Router::class);
        $matchRouteMethod = $reflector->getMethod('matchRoute');
        $matchRouteMethod->setAccessible(true);

        return $matchRouteMethod->invokeArgs(null, [$requestUri, $routes]);
    }
}
