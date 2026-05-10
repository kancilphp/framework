<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Validation {
    protected $data = [];
    protected $rules = [];
    protected $errors = [];

    public function __construct($data, $rules) {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make($data, $rules) {
        return new self($data, $rules);
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

    public function firstError() {
        return $this->errors ? reset($this->errors) : null;
    }

    public function failed() {
        return !empty($this->errors);
    }

    protected function apply($field, $rule) {
        $value = $this->data[$field] ?? null;

        if ($rule === 'required') {
            if ($value === null || $value === '') {
                $this->errors[$field][] = ucfirst($field) . ' is required';
            }
            return;
        }

        if (strpos($rule, 'min:') === 0) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $this->errors[$field][] = ucfirst($field) . ' must be at least ' . $min . ' characters';
            }
            return;
        }

        if ($rule === 'email') {
            if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = ucfirst($field) . ' must be a valid email';
            }
            return;
        }

        if ($rule === 'numeric') {
            if ($value !== null && !is_numeric($value)) {
                $this->errors[$field][] = ucfirst($field) . ' must be numeric';
            }
            return;
        }

        if (strpos($rule, 'unique:') === 0) {
            $table = substr($rule, 7);
            $existing = DB::first("SELECT id FROM {$table} WHERE {$field} = ?", [$value]);
            if ($existing) {
                $this->errors[$field][] = ucfirst($field) . ' already exists';
            }
            return;
        }
    }
}
