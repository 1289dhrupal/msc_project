<?php

use PHPUnit\Framework\TestCase;
use MscProject\Models\User;

class UserTest extends TestCase
{
    public function testUserConstructor()
    {
        $id = 1;
        $name = 'John Doe';
        $email = 'john@example.com';
        $password = 'hashedpassword';
        $status = 'active';

        $user = new User($id, $name, $email, $password, $status);

        $this->assertEquals($id, $user->getId());
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($password, $user->getPassword());
        $this->assertEquals($status, $user->getStatus());
    }

    public function testSettersAndGetters()
    {
        $user = new User(1, 'John Doe', 'john@example.com', 'hashedpassword', 'active');

        $user->setName('Jane Doe');
        $user->setPassword('newpassword');

        $this->assertEquals('Jane Doe', $user->getName());
        $this->assertEquals('newpassword', $user->getPassword());
    }
}
