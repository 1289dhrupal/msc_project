<?php

use PHPUnit\Framework\TestCase;
use MscProject\Models\Alert;

class AlertTest extends TestCase
{
    public function testAlertConstructor()
    {
        $userId = 1;
        $inactivity = true;
        $sync = false;
        $realtime = true;

        $alert = new Alert($userId, $inactivity, $sync, $realtime);

        $this->assertEquals($userId, $alert->getUserId());
        $this->assertTrue($alert->getInactivity());
        $this->assertFalse($alert->getSync());
        $this->assertTrue($alert->getRealtime());
    }

    public function testDefaultConstructor()
    {
        $userId = 1;
        $alert = new Alert($userId);

        $this->assertEquals($userId, $alert->getUserId());
        $this->assertTrue($alert->getInactivity()); // Default should be true
        $this->assertTrue($alert->getSync()); // Default should be true
        $this->assertTrue($alert->getRealtime()); // Default should be true
    }

    public function testSettersAndGetters()
    {
        $alert = new Alert(1);

        $alert->setInactivity(true);
        $alert->setSync(true);
        $alert->setRealtime(false);

        $this->assertTrue($alert->getInactivity());
        $this->assertTrue($alert->getSync());
        $this->assertFalse($alert->getRealtime());
    }

    public function testToggleInactivity()
    {
        $alert = new Alert(1);
        $alert->setInactivity(true);
        $this->assertTrue($alert->getInactivity());

        $alert->setInactivity(false);
        $this->assertFalse($alert->getInactivity());
    }

    public function testToggleSync()
    {
        $alert = new Alert(1);
        $alert->setSync(true);
        $this->assertTrue($alert->getSync());

        $alert->setSync(false);
        $this->assertFalse($alert->getSync());
    }

    public function testToggleRealtime()
    {
        $alert = new Alert(1);
        $alert->setRealtime(true);
        $this->assertTrue($alert->getRealtime());

        $alert->setRealtime(false);
        $this->assertFalse($alert->getRealtime());
    }
}
