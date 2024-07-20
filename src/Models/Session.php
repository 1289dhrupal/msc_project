<?php

namespace MscProject\Models;

class Session
{
    public $userId;
    public $apiKey;
    public $createdAt;

    public function __construct($userId, $apiKey, $createdAt)
    {
        $this->userId = $userId;
        $this->apiKey = $apiKey;
        $this->createdAt = $createdAt;
    }
}
