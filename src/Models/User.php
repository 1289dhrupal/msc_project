<?php

declare(strict_types=1);

namespace MscProject\Models;

class User
{
    private ?int $id;
    private string $name;
    private string $email;
    private string $password;
    private string $status;

    public function __construct(?int $id, string $name, string $email, string $password, string $status)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->status = $status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
