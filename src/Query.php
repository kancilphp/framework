<?php
// KancilPHP Framework - MIT License
// Copyright (c) 2026 Pino Ruswana <pino.ruswana@gmail.com>
// Bandung - Indonesia

namespace Core;
class Query {
    protected $table;
    protected $wheres = [];
    protected $params = [];
    protected $limit = null;
    protected $orderBy = null;
    protected $columns = '*';

    public function __construct($table) {
        $this->table = $table;
    }

    public static function table($table) {
        return new self($table);
    }

    public function select($columns) {
        $this->columns = is_array($columns) ? implode(',', $columns) : $columns;
        return $this;
    }

    public function where($column, $value) {
        $this->wheres[] = $column . ' = ?';
        $this->params[] = $value;
        return $this;
    }

    public function whereIn($column, $values) {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = $column . ' IN (' . $placeholders . ')';
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function whereLike($column, $value) {
        $this->wheres[] = $column . ' LIKE ?';
        $this->params[] = $value;
        return $this;
    }

    public function limit($limit) {
        $this->limit = (int) $limit;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return $this;
        }
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy = $column . ' ' . $dir;
        return $this;
    }

    public function get() {
        $sql = 'SELECT ' . $this->columns . ' FROM ' . $this->table;
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        if ($this->orderBy) {
            $sql .= ' ORDER BY ' . $this->orderBy;
        }
        if ($this->limit) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        return DB::query($sql, $this->params);
    }

    public function first() {
        $this->limit = 1;
        $result = $this->get();
        return $result ? $result[0] : null;
    }

    public function count() {
        $sql = 'SELECT COUNT(*) as total FROM ' . $this->table;
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        $result = DB::first($sql, $this->params);
        return (int) ($result['total'] ?? 0);
    }

    public function insert($data) {
        $columns = implode(',', array_keys($data));
        $placeholders = implode(',', array_fill(0, count($data), '?'));
        $sql = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders . ')';
        DB::execute($sql, array_values($data));
        return DB::lastInsertId();
    }

    public function update($data) {
        $set = [];
        $setParams = [];
        foreach ($data as $key => $value) {
            $set[] = $key . ' = ?';
            $setParams[] = $value;
        }
        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $set);
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        return DB::execute($sql, array_merge($setParams, $this->params));
    }

    public function delete() {
        if (empty($this->wheres)) return false;
        $sql = 'DELETE FROM ' . $this->table . ' WHERE ' . implode(' AND ', $this->wheres);
        return DB::execute($sql, $this->params);
    }
}
