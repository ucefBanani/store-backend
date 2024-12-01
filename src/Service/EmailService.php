<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    private MailerInterface $mailer;
    private Environment $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * Sends an email with the specified parameters.
     *
     * @param string $to The recipient email address.
     * @param string $subject The email subject.
     * @param string $template The Twig template for the email content.
     * @param array $context Data to pass to the Twig template.
     * @param string|null $from The sender email address (optional).
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        string $from = 'noreply@store.com'
    ): void {
        $htmlContent = $this->twig->render($template, $context);

        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        $this->mailer->send($email);
    }
}
