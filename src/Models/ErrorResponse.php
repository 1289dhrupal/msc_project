<?php

declare(strict_types=1);

namespace MscProject\Models;

class ErrorResponse extends Response
{
    private ?int $code;
    private ?string $error;

    public function __construct(string $message, string $error, int $statusCode = 400, int $code = null, array $headers = [])
    {
        parent::__construct($message, $statusCode, $headers);
        $this->code = $code;
        $this->error = $error;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function toArray(): array
    {
        $response = ['success' => false, 'message' => $this->message];
        if ($this->code !== null) {
            $response['code'] = $this->code;
        }
        if ($this->error !== null) {
            $response['error'] = $this->error;
        }
        return $response;
    }
}
