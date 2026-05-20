<?php

namespace App\Core;

class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];

    public function all(string $orderBy = 'id DESC', string $where = '', array $params = []): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }
        if ($orderBy !== '') {
            $sql .= " ORDER BY {$orderBy}";
        }
        return Database::query($sql, $params)->fetchAll();
    }

    public function paginate(int $limit = 20, int $offset = 0, string $orderBy = 'id DESC', string $where = '', array $params = []): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }
        $sql .= " ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";
        $stmt = Database::connect()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(is_int($key) ? $key + 1 : ':' . ltrim($key, ':'), $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function find(int|string $id): ?array
    {
        $stmt = Database::query("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1", [$id]);
        return $stmt->fetch() ?: null;
    }

    public function first(string $where, array $params = []): ?array
    {
        $stmt = Database::query("SELECT * FROM {$this->table} WHERE {$where} LIMIT 1", $params);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $data = $this->onlyFillable($data);
        if (empty($data)) {
            return 0;
        }
        $columns = array_keys($data);
        $placeholders = array_map(fn ($column) => ':' . $column, $columns);
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        Database::query($sql, $data);
        return (int) Database::lastInsertId();
    }

    public function update(int|string $id, array $data): bool
    {
        $data = $this->onlyFillable($data);
        if (empty($data)) {
            return false;
        }
        $sets = array_map(fn ($column) => "{$column} = :{$column}", array_keys($data));
        $data[$this->primaryKey] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->primaryKey} = :{$this->primaryKey}";
        Database::query($sql, $data);
        return true;
    }

    public function delete(int|string $id): bool
    {
        Database::query("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
        return true;
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table}";
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }
        return (int) (Database::query($sql, $params)->fetch()['total'] ?? 0);
    }

    protected function onlyFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }
}
