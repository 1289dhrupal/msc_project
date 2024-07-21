<?php

namespace MscProject\Models;

class Session
{
    public $userId;
    public $apiKey;
    public $createdAt;

    public function __construct($userId, $apiKey)
    {
        $this->userId = $userId;
        $this->apiKey = $apiKey;
    }
}
