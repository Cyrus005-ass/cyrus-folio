<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class MailService
{
    public static function sendContactNotification(array $contact): bool
    {
        $to = trim((string) env('MAIL_TO', env('MAIL_FROM', '')));
        $from = trim((string) env('MAIL_FROM', 'noreply@portfolio.local'));

        if (!is_valid_email($to) || !is_valid_email($from) || !class_exists(PHPMailer::class)) {
            return false;
        }

        [$replyToEmail, $replyToName] = self::resolveReplyTo($contact);

        try {
            $mail = self::createMailer();
            $mail->setFrom($from, trim((string) env('MAIL_FROM_NAME', (string) env('APP_NAME', 'Cyrus-y ASSOGBA'))));
            $mail->addAddress($to, trim((string) env('MAIL_TO_NAME', '')));

            if ($replyToEmail !== null) {
                $mail->addReplyTo($replyToEmail, $replyToName);
            }

            $mail->Subject = '[' . trim((string) env('APP_NAME', 'Cyrus-y ASSOGBA')) . '] Nouveau message : ' . ($contact['sujet'] ?? 'Contact');
            $mail->Body = self::contactBody($contact);

            return $mail->send();
        } catch (Throwable) {
            return false;
        }
    }

    private static function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Encoding = PHPMailer::ENCODING_BASE64;
        $mail->isHTML(false);
        $mail->Timeout = max(5, (int) env('MAIL_TIMEOUT', 15));
        $mail->Hostname = self::ehloDomain();
        $mail->XMailer = trim((string) env('APP_NAME', 'Cyrus-y ASSOGBA')) . ' PHPMailer';

        if (self::shouldUseSmtp()) {
            self::configureSmtp($mail);
        } else {
            $mail->isMail();
        }

        return $mail;
    }

    private static function configureSmtp(PHPMailer $mail): void
    {
        $config = self::smtpConfig();
        if ($config['host'] === '') {
            throw new \RuntimeException('SMTP host missing.');
        }

        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Port = $config['port'];
        $mail->Timeout = $config['timeout'];
        $mail->Hostname = $config['ehlo_domain'];
        $mail->Helo = $config['ehlo_domain'];
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => $config['verify_peer'],
                'verify_peer_name' => $config['verify_peer_name'],
                'allow_self_signed' => !$config['verify_peer'],
            ],
        ];

        if ($config['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->SMTPAutoTLS = false;
        } elseif ($config['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAutoTLS = true;
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = false;
        }

        if ($config['username'] !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
        } else {
            $mail->SMTPAuth = false;
        }
    }

    private static function resolveReplyTo(array $contact): array
    {
        $contactEmail = trim((string) ($contact['email'] ?? ''));
        if (is_valid_email($contactEmail)) {
            return [$contactEmail, trim((string) ($contact['nom'] ?? ''))];
        }

        $replyTo = trim((string) env('MAIL_REPLY_TO', ''));
        if (is_valid_email($replyTo)) {
            $replyToName = trim((string) env('MAIL_FROM_NAME', (string) env('APP_NAME', 'Cyrus-y ASSOGBA')));
            return [$replyTo, $replyToName];
        }

        return [null, ''];
    }

    private static function contactBody(array $contact): string
    {
        $lines = [
            'Nouveau message depuis le formulaire de contact.',
            '',
            'Nom: ' . trim((string) ($contact['nom'] ?? '')),
            'Email: ' . trim((string) ($contact['email'] ?? '')),
            'Sujet: ' . trim((string) ($contact['sujet'] ?? '')),
            '',
            trim((string) ($contact['message'] ?? '')),
        ];

        return trim(implode(PHP_EOL, $lines));
    }

    private static function shouldUseSmtp(): bool
    {
        $mailer = strtolower(trim((string) env('MAIL_MAILER', 'mail')));
        $host = trim((string) env('MAIL_HOST', ''));

        return in_array($mailer, ['smtp', 'smtps'], true) || $host !== '';
    }

    private static function smtpConfig(): array
    {
        $mailer = strtolower(trim((string) env('MAIL_MAILER', 'mail')));
        $host = trim((string) env('MAIL_HOST', ''));
        $encryption = strtolower(trim((string) env('MAIL_ENCRYPTION', '')));
        if ($encryption === '') {
            $encryption = $mailer === 'smtps' ? 'ssl' : 'none';
        }

        $encryption = match ($encryption) {
            'ssl', 'smtps' => 'ssl',
            'tls', 'starttls' => 'tls',
            default => 'none',
        };

        $timeout = max(5, (int) env('MAIL_TIMEOUT', 15));
        $verifyPeer = (bool) env('MAIL_VERIFY_PEER', true);
        $verifyPeerName = (bool) env('MAIL_VERIFY_PEER_NAME', $verifyPeer);
        $username = self::smtpUsername($host);
        $password = self::normalizeSmtpPassword((string) env('MAIL_PASSWORD', ''), $host);

        return [
            'host' => $host,
            'port' => max(1, (int) env('MAIL_PORT', $encryption === 'ssl' ? 465 : 587)),
            'username' => $username,
            'password' => $password,
            'encryption' => $encryption,
            'timeout' => $timeout,
            'verify_peer' => $verifyPeer,
            'verify_peer_name' => $verifyPeerName,
            'ehlo_domain' => self::ehloDomain(),
        ];
    }

    private static function smtpUsername(string $host): string
    {
        $username = trim((string) env('MAIL_USERNAME', ''));
        if ($username !== '' && (!self::isGmailHost($host) || is_valid_email($username))) {
            return $username;
        }

        $from = trim((string) env('MAIL_FROM', ''));
        if (is_valid_email($from)) {
            return $from;
        }

        return $username;
    }

    private static function normalizeSmtpPassword(string $password, string $host): string
    {
        $password = trim($password);
        if (!self::isGmailHost($host) || !str_contains($password, ' ')) {
            return $password;
        }

        $compact = preg_replace('/\s+/', '', $password) ?? $password;
        if (strlen($compact) === 16 && ctype_alnum($compact)) {
            return $compact;
        }

        return $password;
    }

    private static function isGmailHost(string $host): bool
    {
        $host = strtolower(trim($host));

        return $host === 'gmail.com' || $host === 'smtp.gmail.com';
    }

    private static function ehloDomain(): string
    {
        $domain = trim((string) env('MAIL_EHLO_DOMAIN', ''));
        if ($domain !== '') {
            return $domain;
        }

        $host = parse_url((string) env('APP_URL', ''), PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            return $host;
        }

        return $_SERVER['SERVER_NAME'] ?? 'localhost';
    }
}
