<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
use RuntimeException;
use Exception;
class HttpClient {
    protected static string $baseUrl = '';
    protected static array $defaultHeaders = [];
    protected static int $defaultTimeout = 30;
    protected static int $defaultRetries = 0;

    public $lastResponse;
    public $lastStatus;
    public $lastHeaders;

    protected $url = '';
    protected $method = 'GET';
    protected $headers = [];
    protected $body = null;
    protected $timeout = 0;
    protected $retries = 0;

    public static function make($url = null) {
        $instance = new self();
        if ($url !== null) $instance->url = $url;
        elseif (self::$baseUrl) $instance->url = self::$baseUrl;
        $instance->headers = self::$defaultHeaders;
        $instance->timeout = self::$defaultTimeout;
        $instance->retries = self::$defaultRetries;
        if (!$instance->timeout) $instance->timeout = 30;
        return $instance;
    }

    public static function get($url, $headers = []) {
        return self::make($url)->headers($headers)->send('GET');
    }

    public static function post($url, $data = [], $headers = []) {
        return self::make($url)->headers($headers)->body($data)->send('POST');
    }

    public static function put($url, $data = [], $headers = []) {
        return self::make($url)->headers($headers)->body($data)->send('PUT');
    }

    public static function patch($url, $data = [], $headers = []) {
        return self::make($url)->headers($headers)->body($data)->send('PATCH');
    }

    public static function delete($url, $headers = []) {
        return self::make($url)->headers($headers)->send('DELETE');
    }

    public static function baseUrl($url) {
        self::$baseUrl = rtrim($url, '/');
        return self::class;
    }

    public static function withOptions(array $options) {
        if (isset($options['base_url'])) self::baseUrl($options['base_url']);
        if (isset($options['timeout'])) self::$defaultTimeout = $options['timeout'];
        if (isset($options['headers'])) self::$defaultHeaders = array_merge(self::$defaultHeaders, $options['headers']);
        if (isset($options['token'])) self::$defaultHeaders['Authorization'] = 'Bearer ' . $options['token'];
        return self::class;
    }

    public static function reset() {
        self::$baseUrl = '';
        self::$defaultHeaders = [];
        self::$defaultTimeout = 30;
        self::$defaultRetries = 0;
    }

    // Instance methods for fluent chaining
    public function headers(array $headers) {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function body($data) {
        $this->body = is_array($data) ? json_encode($data) : $data;
        if (!isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = 'application/json';
        }
        return $this;
    }

    public function asForm() {
        if ($this->body && isset($this->headers['Content-Type'])) {
            $decoded = json_decode($this->body, true);
            if ($decoded !== null) {
                $this->body = http_build_query($decoded);
            }
        }
        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        return $this;
    }

    public function attach($name, $path, $filename = null) {
        if (!isset($this->headers['_files'])) $this->headers['_files'] = [];
        $this->headers['_files'][] = [
            'name' => $name,
            'path' => $path,
            'filename' => $filename ?: basename($path),
        ];
        unset($this->headers['Content-Type']);
        return $this;
    }

    public function timeout($seconds) {
        $this->timeout = $seconds;
        return $this;
    }

    public function retries($count) {
        $this->retries = $count;
        return $this;
    }

    public function asJson() {
        $this->headers['Content-Type'] = 'application/json';
        $this->headers['Accept'] = 'application/json';
        return $this;
    }

    public function withHeaders(array $headers) {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function withToken($token) {
        $this->headers['Authorization'] = 'Bearer ' . $token;
        return $this;
    }

    public function send($method = 'GET') {
        $this->method = strtoupper($method);
        $url = $this->buildUrl();
        $attempts = $this->retries + 1;
        $response = null;
        $lastError = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $response = $this->execute($url);
                $lastError = null;
                break;
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                if ($i < $attempts - 1) usleep(100000 * ($i + 1));
            }
        }

        if ($response === null && $lastError) {
            throw new \RuntimeException("HTTP request failed: $lastError");
        }

        return $response;
    }

    protected function buildUrl() {
        if (self::$baseUrl && !filter_var($this->url, FILTER_VALIDATE_URL)) {
            return rtrim(self::$baseUrl, '/') . '/' . ltrim($this->url, '/');
        }
        return $this->url;
    }

    protected function execute($url) {
        $ch = curl_init();
        if (!$ch) throw new \RuntimeException('curl_init() failed');

        $hasFiles = isset($this->headers['_files']) && !empty($this->headers['_files']);
        $isJson = isset($this->headers['Content-Type']) && strpos($this->headers['Content-Type'], 'application/json') !== false;

        if ($this->method === 'GET' && $this->body) {
            $query = $isJson ? http_build_query(json_decode($this->body, true)) : $this->body;
            $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
            $this->body = null;
        }

        $headers = [];
        $files = $this->headers['_files'] ?? [];
        unset($this->headers['_files']);
        foreach ($this->headers as $k => $v) $headers[] = "$k: $v";

        if ($hasFiles) {
            $boundary = md5(uniqid());
            $headers[] = "Content-Type: multipart/form-data; boundary=$boundary";
            $this->body = $this->buildMultipartBody($boundary);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CUSTOMREQUEST  => $this->method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERAGENT      => 'KancilHttpClient/1.0',
        ]);

        if ($this->body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException($err);
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $rawHeaders = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        $parsedHeaders = $this->parseRawHeaders($rawHeaders);

        $contentType = $parsedHeaders['content-type'] ?? '';
        if (strpos($contentType, 'application/json') !== false || strpos($contentType, 'json') !== false) {
            $decoded = json_decode($body, true);
            $body = $decoded !== null ? $decoded : $body;
        }

        $result = [
            'body'    => $body,
            'status'  => $httpCode,
            'headers' => $parsedHeaders,
        ];

        $this->lastResponse = $result;
        $this->lastStatus = $httpCode;
        $this->lastHeaders = $parsedHeaders;

        return $result;
    }

    protected function buildMultipartBody($boundary) {
        $body = '';
        $data = json_decode($this->body, true) ?: [];
        foreach ($data as $key => $value) {
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
            $body .= "$value\r\n";
        }
        foreach ($this->headers['_files'] ?? [] as $file) {
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$file['name']}\"; filename=\"{$file['filename']}\"\r\n";
            $body .= "Content-Type: application/octet-stream\r\n\r\n";
            $body .= file_get_contents($file['path']) . "\r\n";
        }
        $body .= "--$boundary--\r\n";
        return $body;
    }

    protected function parseRawHeaders($raw) {
        $headers = [];
        foreach (explode("\r\n", trim($raw)) as $line) {
            if (strpos($line, ':') === false) continue;
            list($key, $value) = explode(':', $line, 2);
            $headers[strtolower(trim($key))] = trim($value);
        }
        return $headers;
    }
}
