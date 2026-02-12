<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findAllWithUsage(): array
    {
        $sql = 'SELECT c.id, c.name, c.created_at, COUNT(e.id) AS expense_count
                FROM categories c
                LEFT JOIN expenses e ON e.category_id = c.id
                GROUP BY c.id, c.name, c.created_at
                ORDER BY c.name ASC';

        return $this->db->query($sql)->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, created_at FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId === null) {
            $stmt = $this->db->prepare('SELECT 1 FROM categories WHERE LOWER(name) = LOWER(:name) LIMIT 1');
            $stmt->execute(['name' => $name]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT 1 FROM categories WHERE LOWER(name) = LOWER(:name) AND id <> :id LIMIT 1'
            );
            $stmt->execute([
                'name' => $name,
                'id' => $excludeId,
            ]);
        }

        return $stmt->fetchColumn() !== false;
    }

    public function create(string $name): void
    {
        $stmt = $this->db->prepare('INSERT INTO categories (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);
    }

    public function createAndReturnId(string $name): int
    {
        $stmt = $this->db->prepare('INSERT INTO categories (name) VALUES (:name)');
        $stmt->execute(['name' => $name]);

        return (int) $this->db->lastInsertId();
    }

    public function findIdByName(string $name): ?int
    {
        $stmt = $this->db->prepare('SELECT id FROM categories WHERE LOWER(name) = LOWER(:name) LIMIT 1');
        $stmt->execute(['name' => $name]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function updateName(int $id, string $name): void
    {
        $stmt = $this->db->prepare('UPDATE categories SET name = :name WHERE id = :id');
        $stmt->execute([
            'name' => $name,
            'id' => $id,
        ]);
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findAllForSelect(): array
    {
        $sql = 'SELECT id, name FROM categories ORDER BY name ASC';

        return $this->db->query($sql)->fetchAll() ?: [];
    }
}
