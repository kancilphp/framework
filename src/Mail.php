<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Mail {
    protected $to;
    protected $subject;
    protected $body;
    protected $headers = [];

    public function __construct($to = null) {
        $this->to = $to;
        $this->headers['MIME-Version'] = '1.0';
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
    }

    public static function to($to) {
        return new self($to);
    }

    public function subject($subject) {
        $this->subject = $subject;
        return $this;
    }

    public function body($body) {
        $this->body = $body;
        return $this;
    }

    public function from($email, $name = '') {
        $from = $name ? "$name <$email>" : $email;
        $this->headers['From'] = $from;
        return $this;
    }

    public function replyTo($email) {
        $this->headers['Reply-To'] = $email;
        return $this;
    }

    public function send() {
        if (!$this->to || !$this->subject || !$this->body) {
            return false;
        }

        $headerLines = [];
        foreach ($this->headers as $key => $value) {
            $headerLines[] = "$key: $value";
        }

        $from = Config::get('MAIL_FROM');
        if ($from && !isset($this->headers['From'])) {
            $headerLines[] = "From: $from";
        }

        return mail($this->to, $this->subject, $this->body, implode("\r\n", $headerLines));
    }
}
