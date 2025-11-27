<?php

declare(strict_types=1);

namespace MscProject\Models\Response;

abstract class Response
{
    protected string $message;
    protected int $statusCode;
    protected array $headers;
    protected bool $isSent = false;

    public function __construct(string $message, int $statusCode, array $headers = [])
    {
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    // Getters
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

    // Setters
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    // Sends the response
    public function send(): void
    {
        if ($this->isSent) {
            return;
        }

        http_response_code($this->statusCode);
        foreach ($this->headers as $header) {
            header($header);
        }
        header('Content-Type: application/json');
        echo $this->toJson();
        $isSent = true;
    }

    // Abstract method that must be implemented by subclasses
    abstract public function toArray(): array;

    // Converts the response to JSON
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
