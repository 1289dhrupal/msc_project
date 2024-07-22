<?php

declare(strict_types=1);

namespace MscProject\Models;

abstract class Response
{
    protected string $message;
    protected int $statusCode;
    protected array $headers;

    public function __construct(string $message, int $statusCode, array $headers = [])
    {
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $header) {
            header($header);
        }
        header('Content-Type: application/json');
        echo $this->toJson();
    }

    abstract public function toArray(): array;

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
