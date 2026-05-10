<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Pagination {
    protected $total;
    protected $perPage;
    protected $currentPage;

    public function __construct($total, $perPage = 20, $page = 1) {
        $this->total = (int) $total;
        $this->perPage = (int) $perPage;
        $this->currentPage = max(1, (int) $page);
    }

    public static function make($total, $perPage = 20, $page = null) {
        $page = $page ?? ($_GET['page'] ?? 1);
        return new self($total, $perPage, $page);
    }

    public function offset() {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function totalPages() {
        return (int) ceil($this->total / $this->perPage);
    }

    public function currentPage() {
        return $this->currentPage;
    }

    public function hasNext() {
        return $this->currentPage < $this->totalPages();
    }

    public function hasPrev() {
        return $this->currentPage > 1;
    }

    public function toArray() {
        return [
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'total_pages' => $this->totalPages(),
            'has_next' => $this->hasNext(),
            'has_prev' => $this->hasPrev(),
        ];
    }
}
