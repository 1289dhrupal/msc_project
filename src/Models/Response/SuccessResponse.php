<?php

declare(strict_types=1);

namespace MscProject\Models\Response;

class SuccessResponse extends Response
{
    private mixed $data;

    public function __construct(string $message, mixed $data = null, int $statusCode = 200, array $headers = [])
    {
        parent::__construct($message, $statusCode, $headers);
        $this->data = $data;
    }

    // Getters
    public function getData(): mixed
    {
        return $this->data;
    }

    // Setters
    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        $response = [
            'success' => true,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        return $response;
    }
}
