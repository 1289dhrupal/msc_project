<?php

declare(strict_types=1);

namespace MscProject\Tests\Models\Response;

use MscProject\Models\Response\SuccessResponse;
use PHPUnit\Framework\TestCase;

class SuccessResponseTest extends TestCase
{
    public function testCanInstantiateSuccessResponse(): void
    {
        $response = new SuccessResponse('Success message', ['key' => 'value']);

        $this->assertInstanceOf(SuccessResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success message', $response->getMessage());
        $this->assertEquals(['key' => 'value'], $response->getData());
    }

    public function testSendOutputsCorrectJson(): void
    {
        $response = new SuccessResponse('Success message', ['key' => 'value']);

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $expectedOutput = json_encode([
            'success' => true,
            'message' => 'Success message',
            'data' => ['key' => 'value']
        ]);

        $this->assertJsonStringEqualsJsonString($expectedOutput, $output);
    }

    public function testCanSetAndGetMessage(): void
    {
        $response = new SuccessResponse('Initial message');
        $response->setMessage('Updated message');

        $this->assertEquals('Updated message', $response->getMessage());
    }

    public function testCanSetAndGetData(): void
    {
        $response = new SuccessResponse('Initial message', ['initialKey' => 'initialValue']);
        $response->setData(['newKey' => 'newValue']);

        $this->assertEquals(['newKey' => 'newValue'], $response->getData());
    }

    public function testCanSetAndGetStatus(): void
    {
        $response = new SuccessResponse('Initial message');
        $response->setStatusCode(201);

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testCanSetAndGetHeaders(): void
    {
        $response = new SuccessResponse('Initial message');
        $response->setHeaders(['Content-Type' => 'application/json']);

        $this->assertEquals(['Content-Type' => 'application/json'], $response->getHeaders());
    }

    public function testAddHeader(): void
    {
        $response = new SuccessResponse('Initial message');
        $response->setHeaders(['Authorization' => 'Bearer token']);

        $this->assertEquals(['Authorization' => 'Bearer token'], $response->getHeaders());
    }
}
