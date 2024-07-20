<?php

namespace MscProject\Models;

class User
{
    public $id;
    public $name;
    public $email;
    public $password;
    public $status;
    public $lastAccessed;

    public function __construct($id, $name, $email, $password, $status, $lastAccessed)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->status = $status;
        $this->lastAccessed = $lastAccessed;
    }
}
