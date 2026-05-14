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
    protected $offset = null;
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

    public function where($column, $operator = null, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        if (!in_array($operator, ['=', '<', '>', '<=', '>=', '!=', '<>', 'LIKE', 'NOT LIKE'], true)) {
            return $this;
        }
        $this->wheres[] = $column . ' ' . $operator . ' ?';
        $this->params[] = $value;
        return $this;
    }

    public function whereNull($column) {
        $this->wheres[] = $column . ' IS NULL';
        return $this;
    }

    public function whereNotNull($column) {
        $this->wheres[] = $column . ' IS NOT NULL';
        return $this;
    }

    public function whereIn($column, $values) {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = $column . ' IN (' . $placeholders . ')';
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function whereNotIn($column, $values) {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->wheres[] = $column . ' NOT IN (' . $placeholders . ')';
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    public function whereLike($column, $value) {
        $this->wheres[] = $column . ' LIKE ?';
        $this->params[] = $value;
        return $this;
    }

    public function whereBetween($column, $min, $max) {
        $this->wheres[] = $column . ' BETWEEN ? AND ?';
        $this->params[] = $min;
        $this->params[] = $max;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $column)) {
            return $this;
        }
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy = $column . ' ' . $dir;
        return $this;
    }

    public function limit($limit) {
        $this->limit = max(1, (int) $limit);
        return $this;
    }

    public function offset($offset) {
        $this->offset = max(0, (int) $offset);
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
        if ($this->offset) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        return DB::query($sql, $this->params);
    }

    public function first() {
        $this->limit = 1;
        $result = $this->get();
        return $result ? $result[0] : null;
    }

    public function value($column) {
        $this->select($column)->limit(1);
        $result = $this->get();
        return $result ? $result[0][$column] : null;
    }

    public function pluck($column, $key = null) {
        $rows = $this->select([$column, $key ?? 'id'])->get();
        $result = [];
        foreach ($rows as $row) {
            if ($key) {
                $result[$row[$key]] = $row[$column];
            } else {
                $result[] = $row[$column];
            }
        }
        return $result;
    }

    public function count() {
        $sql = 'SELECT COUNT(*) as total FROM ' . $this->table;
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        $result = DB::first($sql, $this->params);
        return (int) ($result['total'] ?? 0);
    }

    public function paginate($perPage = 20, $page = null) {
        $page = $page ?? ($_GET['page'] ?? 1);
        $total = $this->count();
        $page = max(1, (int) $page);
        $offset = ($page - 1) * $perPage;

        $this->limit($perPage)->offset($offset);
        $data = $this->get();

        $pages = (int) ceil($total / $perPage);
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $pages,
            'has_next' => $page < $pages,
            'has_prev' => $page > 1,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
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

    public function increment($column, $amount = 1) {
        $sql = 'UPDATE ' . $this->table . ' SET ' . $column . ' = ' . $column . ' + ?';
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        return DB::execute($sql, array_merge([$amount], $this->params));
    }

    public function decrement($column, $amount = 1) {
        return $this->increment($column, -$amount);
    }

    public function delete() {
        if (empty($this->wheres)) return false;
        $sql = 'DELETE FROM ' . $this->table . ' WHERE ' . implode(' AND ', $this->wheres);
        return DB::execute($sql, $this->params);
    }

    public function toSql() {
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
        if ($this->offset) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        return $sql;
    }
}
