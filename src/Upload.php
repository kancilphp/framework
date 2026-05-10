<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Upload {
    protected $file;
    protected $allowed = [];
    protected $maxSize = 5242880;
    protected $name;

    public function __construct($inputName) {
        $this->file = $_FILES[$inputName] ?? null;
    }

    public static function make($inputName) {
        return new self($inputName);
    }

    public function allowed($types) {
        $this->allowed = $types;
        return $this;
    }

    public function maxSize($bytes) {
        $this->maxSize = $bytes;
        return $this;
    }

    public function name($name) {
        $this->name = $name;
        return $this;
    }

    public function error() {
        if (!$this->file) return 'No file uploaded';
        if ($this->file['error'] !== UPLOAD_ERR_OK) return 'Upload failed';
        if ($this->file['size'] > $this->maxSize) return 'File too large';
        if (!empty($this->allowed)) {
            $ext = strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowed)) return 'File type not allowed';
        }
        return null;
    }

    public function store($directory) {
        $err = $this->error();
        if ($err) return false;

        $dir = BASE_PATH . '/' . ltrim($directory, '/');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = $this->name ?: uniqid() . '.' . pathinfo($this->file['name'], PATHINFO_EXTENSION);
        $path = $dir . '/' . $filename;

        return move_uploaded_file($this->file['tmp_name'], $path) ? $directory . '/' . $filename : false;
    }
}
