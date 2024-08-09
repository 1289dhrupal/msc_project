<?php

declare(strict_types=1);

namespace MscProject;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class Mailer
{
    private static ?Mailer $instance = null;
    private PHPMailer $mail;

    private function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    public static function getInstance(): Mailer
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function configure(): void
    {
        try {
            $this->mail->isSMTP();
            $this->mail->Host = $_ENV['SMTP_HOST'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $_ENV['SMTP_USER'];
            $this->mail->Password = $_ENV['SMTP_PASS'];
            $this->mail->SMTPSecure = $_ENV['SMTP_SECURE'];
            $this->mail->Port = (int)$_ENV['SMTP_PORT'];
            $this->mail->setFrom($_ENV['SMTP_FROM'], $_ENV['SMTP_FROM_NAME']);
        } catch (MailException $e) {
            throw new \ErrorException('Mailer configuration error: ' . $e->getMessage(), 500);
        }
    }

    public function sendEmail(string $to, string $subject, string $body, array $attachments = []): void
    {
        try {
            // Recipients
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);

            // Attachments
            foreach ($attachments as $attachment) {
                $this->mail->addAttachment($attachment);
            }

            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;

            $this->mail->send();

            // echo "Email has been sent to {$to}.";
        } catch (MailException $e) {
            // echo "Email could not be sent to {$to}. Mailer Error: {$this->mail->ErrorInfo}";
        }
    }
}
