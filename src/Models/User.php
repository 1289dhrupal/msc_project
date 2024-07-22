<?php

declare(strict_types=1);

namespace MscProject\Models;

class User
{
    public ?int $id;
    public string $name;
    public string $email;
    public string $password;
    public string $status;

    public function __construct(?int $id, string $name, string $email, string $password, string $status)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->status = $status;
    }
}
