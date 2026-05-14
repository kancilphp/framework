<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Validation {
    protected $data = [];
    protected $rules = [];
    protected $errors = [];
    protected $messages = [];

    public function __construct($data, $rules, $messages = []) {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public static function make($data, $rules, $messages = []) {
        return new self($data, $rules, $messages);
    }

    public function validate() {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            foreach ($rules as $rule) {
                $this->apply($field, $rule);
            }
        }
        return empty($this->errors);
    }

    public function errors() {
        return $this->errors;
    }

    public function fails() {
        return !empty($this->errors);
    }

    public function firstError() {
        return $this->errors ? reset($this->errors)[0] ?? null : null;
    }

    protected function msg($field, $key, $default) {
        return $this->messages[$field . '.' . $key] ?? $this->messages[$field] ?? $default;
    }

    protected function apply($field, $rule) {
        $value = $this->data[$field] ?? null;

        if ($rule === 'required') {
            if ($value === null || $value === '') {
                $this->errors[$field][] = $this->msg($field, 'required', ucfirst($field) . ' wajib diisi');
            }
            return;
        }

        if ($value === null || $value === '') return;

        if (strpos($rule, 'min:') === 0) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $this->errors[$field][] = $this->msg($field, 'min', ucfirst($field) . ' minimal ' . $min . ' karakter');
            }
            return;
        }

        if (strpos($rule, 'max:') === 0) {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                $this->errors[$field][] = $this->msg($field, 'max', ucfirst($field) . ' maksimal ' . $max . ' karakter');
            }
            return;
        }

        if (strpos($rule, 'between:') === 0) {
            $parts = explode(',', substr($rule, 8));
            $min = (int) $parts[0]; $max = (int) $parts[1];
            $len = strlen($value);
            if ($len < $min || $len > $max) {
                $this->errors[$field][] = $this->msg($field, 'between', ucfirst($field) . ' harus antara ' . $min . ' dan ' . $max . ' karakter');
            }
            return;
        }

        if ($rule === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = $this->msg($field, 'email', ucfirst($field) . ' harus email valid');
            }
            return;
        }

        if ($rule === 'numeric') {
            if (!is_numeric($value)) {
                $this->errors[$field][] = $this->msg($field, 'numeric', ucfirst($field) . ' harus angka');
            }
            return;
        }

        if ($rule === 'integer') {
            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                $this->errors[$field][] = $this->msg($field, 'integer', ucfirst($field) . ' harus bilangan bulat');
            }
            return;
        }

        if ($rule === 'alpha') {
            if (!ctype_alpha($value)) {
                $this->errors[$field][] = $this->msg($field, 'alpha', ucfirst($field) . ' hanya boleh huruf');
            }
            return;
        }

        if ($rule === 'url') {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                $this->errors[$field][] = $this->msg($field, 'url', ucfirst($field) . ' harus URL valid');
            }
            return;
        }

        if ($rule === 'date') {
            if (!strtotime($value)) {
                $this->errors[$field][] = $this->msg($field, 'date', ucfirst($field) . ' harus tanggal valid');
            }
            return;
        }

        if ($rule === 'confirmed') {
            $conf = $this->data[$field . '_confirmation'] ?? null;
            if ($value !== $conf) {
                $this->errors[$field][] = $this->msg($field, 'confirmed', ucfirst($field) . ' konfirmasi tidak cocok');
            }
            return;
        }

        if (strpos($rule, 'same:') === 0) {
            $other = substr($rule, 5);
            $otherVal = $this->data[$other] ?? null;
            if ($value !== $otherVal) {
                $this->errors[$field][] = $this->msg($field, 'same', ucfirst($field) . ' harus sama dengan ' . $other);
            }
            return;
        }

        if (strpos($rule, 'different:') === 0) {
            $other = substr($rule, 10);
            $otherVal = $this->data[$other] ?? null;
            if ($value === $otherVal) {
                $this->errors[$field][] = $this->msg($field, 'different', ucfirst($field) . ' harus berbeda dengan ' . $other);
            }
            return;
        }

        if (strpos($rule, 'regex:') === 0) {
            $pattern = substr($rule, 6);
            if (!preg_match($pattern, $value)) {
                $this->errors[$field][] = $this->msg($field, 'regex', ucfirst($field) . ' format tidak valid');
            }
            return;
        }

        if (strpos($rule, 'unique:') === 0) {
            $table = substr($rule, 7);
            $existing = DB::first("SELECT id FROM {$table} WHERE {$field} = ?", [$value]);
            if ($existing) {
                $this->errors[$field][] = $this->msg($field, 'unique', ucfirst($field) . ' sudah digunakan');
            }
            return;
        }

        if (strpos($rule, 'exists:') === 0) {
            $table = substr($rule, 7);
            $existing = DB::first("SELECT id FROM {$table} WHERE {$field} = ?", [$value]);
            if (!$existing) {
                $this->errors[$field][] = $this->msg($field, 'exists', ucfirst($field) . ' tidak ditemukan');
            }
            return;
        }
    }
}
