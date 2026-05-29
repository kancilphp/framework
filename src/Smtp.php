<?php

namespace Core;

class Smtp
{
    private $smtpConfig = [];
    private $fromEmail;
    private $fromName;
    private $toEmail;
    private $toName;
    private $subject;
    private $message;
    private $attachments = [];
    private $boundary;

    public function __construct()
    {
        $this->boundary = md5(uniqid(time()));
    }

    public function setSMTPConfig($host, $port, $username, $password, $encryption = 'tls')
    {
        $this->smtpConfig = [
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'encryption' => $encryption
        ];
    }

    public function setFrom($email, $name = '')
    {
        $this->fromEmail = $email;
        $this->fromName = $name;
    }

    public function setTo($email, $name = '')
    {
        $this->toEmail = $email;
        $this->toName = $name;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function setHtmlMessage($htmlContent)
    {
        $this->message = $htmlContent;
    }

    public function addAttachment($filePath)
    {
        if (file_exists($filePath)) {
            $this->attachments[] = $filePath;
        } else {
            throw new \Exception("File not found: $filePath");
        }
    }

    public function send()
    {
        if (empty($this->smtpConfig)) {
            throw new \Exception("SMTP configuration is not set.");
        }

        $host       = $this->smtpConfig['host'];
        $port       = $this->smtpConfig['port'];
        $username   = $this->smtpConfig['username'];
        $password   = $this->smtpConfig['password'];
        $encryption = $this->smtpConfig['encryption'];

        $socket = fsockopen(($encryption === 'ssl' ? "ssl://" : "") . $host, $port, $errno, $errstr, 30);
        if (!$socket) {
            throw new \Exception("Failed to connect to SMTP server: $errstr ($errno)");
        }

        $this->getFullResponse($socket);

        $this->smtpCommand($socket, "EHLO localhost");
        if ($encryption === 'tls') {
            $this->smtpCommand($socket, "STARTTLS");
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT);
            $this->smtpCommand($socket, "EHLO localhost");
        }

        $this->smtpCommand($socket, "AUTH LOGIN");
        $this->smtpCommand($socket, base64_encode($username));
        $this->smtpCommand($socket, base64_encode($password));
        $this->smtpCommand($socket, "MAIL FROM:<{$this->fromEmail}>");
        $this->smtpCommand($socket, "RCPT TO:<{$this->toEmail}>");
        $this->smtpCommand($socket, "DATA");

        $emailData  = $this->generateHeaders();
        $emailData .= "\r\n" . $this->generateBody();
        $emailData .= "\r\n.\r\n";

        fwrite($socket, $emailData);
        $this->getFullResponse($socket);

        $this->smtpCommand($socket, "QUIT");
        fclose($socket);
    }

    private function generateHeaders()
    {
        $from = $this->fromName ? "{$this->fromName} <{$this->fromEmail}>" : $this->fromEmail;
        $to   = $this->toName ? "{$this->toName} <{$this->toEmail}>" : $this->toEmail;

        $headers  = "From: $from\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: {$this->subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$this->boundary}\"";

        return $headers;
    }

    private function generateBody()
    {
        $body  = "--{$this->boundary}\r\n";
        $body .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= "{$this->message}\r\n";

        foreach ($this->attachments as $filePath) {
            $fileName = basename($filePath);
            $fileData = chunk_split(base64_encode(file_get_contents($filePath)));

            $body .= "--{$this->boundary}\r\n";
            $body .= "Content-Type: application/octet-stream; name=\"{$fileName}\"\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= "{$fileData}\r\n";
        }

        $body .= "--{$this->boundary}--";

        return $body;
    }

    private function smtpCommand($socket, $command)
    {
        if ($command !== "") {
            fwrite($socket, $command . "\r\n");
        }
        $response = $this->getFullResponse($socket);

        if (preg_match('/^[45]/', $response)) {
            throw new \Exception("SMTP error: $response");
        }

        return $response;
    }

    private function getFullResponse($socket)
    {
        $data = "";
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (isset($str[3]) && $str[3] != '-') {
                break;
            }
        }
        return $data;
    }
}
