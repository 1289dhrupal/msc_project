<?php

use PHPUnit\Framework\TestCase;
use MscProject\Mailer;

class MailerTest extends TestCase
{
    protected function setUp(): void {}

    public function testSendEmailSuccess()
    {
        // Assuming the Mailer uses environment variables for SMTP configuration
        $mailer = Mailer::getInstance();
        $result = $mailer->sendEmail('recipient@example.com', 'Test Subject', 'Test Body');

        $this->assertTrue($result, "Expected sendEmail to return true on success");
    }

    public function testSendEmailFailureInvalidRecipient()
    {
        $mailer = Mailer::getInstance();
        $result = $mailer->sendEmail('', 'Test Subject', 'Test Body');

        $this->assertFalse($result, "Expected sendEmail to return false when recipient email is invalid");
    }

    public function testSendEmailFailureEmptySubject()
    {
        $mailer = Mailer::getInstance();
        $result = $mailer->sendEmail('recipient@example.com', '', 'Test Body');

        $this->assertFalse($result, "Expected sendEmail to return false when subject is empty");
    }

    public function testSendEmailFailureEmptyBody()
    {
        $mailer = Mailer::getInstance();
        $result = $mailer->sendEmail('recipient@example.com', 'Test Subject', '');

        $this->assertFalse($result, "Expected sendEmail to return false when body is empty");
    }
}
