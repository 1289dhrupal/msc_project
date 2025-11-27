<?php

declare(strict_types=1);

namespace MscProject\Tests\Models\Response;

use MscProject\Models\Response\ErrorResponse;
use PHPUnit\Framework\TestCase;

class ErrorResponseTest extends TestCase
{
    public function testCanInstantiateErrorResponse(): void
    {
        $response = new ErrorResponse('Error message', 'Detail message', 500);

        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Error message', $response->getMessage());
        $this->assertEquals('Detail message', $response->getError());
    }

    public function testSendOutputsCorrectJson(): void
    {
        $response = new ErrorResponse('Error message', 'Detail message', 500);

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $expectedOutput = json_encode([
            'success' => false,
            'message' => 'Error message',
            'error' => 'Detail message'
        ]);

        $this->assertJsonStringEqualsJsonString($expectedOutput, $output);
    }

    public function testCanSetAndGetError(): void
    {
        $response = new ErrorResponse('Error message', 'Initial detail');
        $response->setError('Updated detail');

        $this->assertEquals('Updated detail', $response->getError());
    }
}
