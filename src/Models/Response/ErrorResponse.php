<?php

declare(strict_types=1);

namespace MscProject\Models\Response;

class ErrorResponse extends Response
{
    private ?int $code;
    private ?string $error;

    public function __construct(string $message, string $error, int $statusCode = 400, ?int $code = null, array $headers = [])
    {
        parent::__construct($message, $statusCode, $headers);
        $this->code = $code;
        $this->error = $error;
    }

    // Getters
    public function getCode(): ?int
    {
        return $this->code;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    // Setters
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    // Convert the error response to an array
    public function toArray(): array
    {
        $response = [
            'success' => false,
            'message' => $this->message,  // Assuming $message is a protected property in the parent class
        ];

        if ($this->code !== null) {
            $response['code'] = $this->code;
        }

        if ($this->error !== null) {
            $response['error'] = $this->error;
        }

        return $response;
    }
}
