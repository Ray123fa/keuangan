<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ExpenseRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findLatest(int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.id, e.category_id, e.amount, e.description, e.created_at, c.name AS category_name
             FROM expenses e
             INNER JOIN categories c ON c.id = e.category_id
             ORDER BY e.created_at DESC, e.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll() ?: []);
    }

    public function findPaginated(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $params = [];
        $whereSql = $this->buildFilterWhereSql($filters, $params);

        $stmt = $this->db->prepare(
            'SELECT e.id, e.category_id, e.amount, e.description, e.created_at, c.name AS category_name
             FROM expenses e
             INNER JOIN categories c ON c.id = e.category_id
             ' . $whereSql . '
             ORDER BY e.created_at DESC, e.id DESC
             LIMIT :limit OFFSET :offset'
        );

        $this->bindFilterParams($stmt, $params);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $this->normalizeRows($stmt->fetchAll() ?: []);
    }

    public function countFiltered(array $filters): int
    {
        $params = [];
        $whereSql = $this->buildFilterWhereSql($filters, $params);

        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM expenses e
             ' . $whereSql
        );

        $this->bindFilterParams($stmt, $params);
        $stmt->execute();

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function buildFilterWhereSql(array $filters, array &$params): string
    {
        $clauses = [];

        if (isset($filters['category_id']) && is_int($filters['category_id']) && $filters['category_id'] > 0) {
            $clauses[] = 'e.category_id = :category_id';
            $params[':category_id'] = $filters['category_id'];
        }

        if (isset($filters['start_date']) && is_string($filters['start_date']) && $filters['start_date'] !== '') {
            $clauses[] = 'DATE(e.created_at) >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }

        if (isset($filters['end_date']) && is_string($filters['end_date']) && $filters['end_date'] !== '') {
            $clauses[] = 'DATE(e.created_at) <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }

        if ($clauses === []) {
            return '';
        }

        return 'WHERE ' . implode(' AND ', $clauses);
    }

    private function bindFilterParams(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            if ($key === ':category_id') {
                $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
                continue;
            }

            $stmt->bindValue($key, (string) $value, PDO::PARAM_STR);
        }
    }

    private function normalizeRows(array $rows): array
    {
        return array_map(static function (array $row): array {
            $row['amount'] = (int) ($row['amount'] ?? 0);
            $row['category_id'] = (int) ($row['category_id'] ?? 0);
            $row['id'] = (int) ($row['id'] ?? 0);

            return $row;
        }, $rows);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, category_id, amount, description, created_at
             FROM expenses
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (is_array($row)) {
            $row['amount'] = (int) ($row['amount'] ?? 0);
            $row['category_id'] = (int) ($row['category_id'] ?? 0);
            $row['id'] = (int) ($row['id'] ?? 0);
        }

        return $row ?: null;
    }

    public function categoryExists(int $categoryId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $categoryId]);

        return $stmt->fetchColumn() !== false;
    }

    public function create(int $categoryId, int $amount, ?string $description, string $date): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO expenses (category_id, amount, description, created_at)
             VALUES (:category_id, :amount, :description, :created_at)'
        );
        $stmt->execute([
            'category_id' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'created_at' => $date . ' 00:00:00',
        ]);
    }

    public function update(int $id, int $categoryId, int $amount, ?string $description, string $date): void
    {
        $stmt = $this->db->prepare(
            'UPDATE expenses
             SET category_id = :category_id,
                 amount = :amount,
                 description = :description,
                 created_at = :created_at
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'category_id' => $categoryId,
            'amount' => $amount,
            'description' => $description,
            'created_at' => $date . ' 00:00:00',
        ]);
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM expenses WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
