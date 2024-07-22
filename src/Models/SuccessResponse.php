<?php

declare(strict_types=1);

namespace MscProject\Models;

class SuccessResponse extends Response
{
    private $data;

    public function __construct(string $message, $data = null, int $statusCode = 200, array $headers = [])
    {
        parent::__construct($message, $statusCode, $headers);
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function toArray(): array
    {
        $response = ['success' => true, 'message' => $this->message];
        if ($this->data !== null) {
            $response['data'] = $this->data;
        }
        return $response;
    }
}
