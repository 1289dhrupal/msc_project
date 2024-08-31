<?php

declare(strict_types=1);

namespace MscProject\Models;

class Alert
{
    private int $userId;
    private bool $inactivity;
    private bool $sync;
    private bool $realtime;

    public function __construct(int $userId, bool $inactivity = true, bool $sync  = true, bool $realtime = true)
    {
        $this->userId = $userId;
        $this->inactivity = $inactivity;
        $this->sync = $sync;
        $this->realtime = $realtime;
    }


    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getInactivity(): bool
    {
        return $this->inactivity;
    }

    public function getSync(): bool
    {
        return $this->sync;
    }

    public function getRealtime(): bool
    {
        return $this->realtime;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setInactivity(bool $inactivity): void
    {
        $this->inactivity = $inactivity;
    }

    public function setSync(bool $sync): void
    {
        $this->sync = $sync;
    }

    public function setRealtime(bool $realtime): void
    {
        $this->realtime = $realtime;
    }
}
