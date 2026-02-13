<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Infrastructure\Database\Connection;
use App\Repositories\CategoryRepository;
use App\Repositories\ExpenseRepository;
use PDOException;
use Throwable;

final class ExpenseController
{
    private \PDO $db;
    private ExpenseRepository $expenses;
    private CategoryRepository $categories;

    public function __construct()
    {
        $this->db = Connection::get();
        $this->expenses = new ExpenseRepository($this->db);
        $this->categories = new CategoryRepository($this->db);
    }

    public function index(): void
    {
        $queryError = null;

        $categoryFilter = $this->parseOptionalPositiveInt($_GET['category_id'] ?? null, $queryError, 'Filter kategori tidak valid.');
        $startDateFilter = $this->parseOptionalDate($_GET['start_date'] ?? null, $queryError, 'Tanggal mulai tidak valid. Gunakan format YYYY-MM-DD.');
        $endDateFilter = $this->parseOptionalDate($_GET['end_date'] ?? null, $queryError, 'Tanggal akhir tidak valid. Gunakan format YYYY-MM-DD.');

        if ($queryError === null && $startDateFilter !== null && $endDateFilter !== null && $startDateFilter > $endDateFilter) {
            $queryError = 'Rentang tanggal tidak valid. Tanggal mulai harus sebelum atau sama dengan tanggal akhir.';
            $startDateFilter = null;
            $endDateFilter = null;
        }

        $page = $this->parseOptionalPositiveInt($_GET['page'] ?? null, $queryError, null) ?? 1;
        $perPage = 20;

        $filters = [
            'category_id' => $categoryFilter,
            'start_date' => $startDateFilter,
            'end_date' => $endDateFilter,
        ];

        $totalItems = $this->expenses->countFiltered($filters);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $expenses = $this->expenses->findPaginated($filters, $page, $perPage);
        $flashError = Session::consumeFlash('error');

        View::render('expenses/index', [
            'categories' => $this->categories->findAllForSelect(),
            'expenses' => $expenses,
            'filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
            ],
            'success' => Session::consumeFlash('success'),
            'error' => $flashError ?? $queryError,
        ]);
    }

    public function store(): void
    {
        if (!$this->ensureCsrf()) {
            return;
        }

        $errorMessage = null;
        $payload = $this->validateExpensePayload($_POST, $errorMessage);
        if ($payload === null) {
            $this->respondError($errorMessage ?? 'Data pengeluaran tidak valid.', 422);
            return;
        }

        try {
            $this->db->beginTransaction();

            $categoryId = $this->resolveCategoryId($payload['category']);
            if ($categoryId === null || !$this->expenses->categoryExists($categoryId)) {
                $this->db->rollBack();
                $this->respondError('Pilih kategori terlebih dahulu atau ketik kategori baru.', 422);
                return;
            }

            $this->expenses->create(
                $categoryId,
                $payload['amount'],
                $payload['description'],
                $payload['date']
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('Expense store error: ' . $exception->getMessage());
            $this->respondError('Terjadi kesalahan saat menyimpan data.', 500);
            return;
        }

        if ($this->isAjaxRequest()) {
            $this->respondJson([
                'success' => true,
                'message' => 'Pengeluaran berhasil ditambahkan.',
            ]);
            return;
        }

        Session::flash('success', 'Pengeluaran berhasil ditambahkan.');
        $this->redirect('/admin/expenses');
    }

    public function update(): void
    {
        if (!$this->ensureCsrf()) {
            return;
        }

        $id = $this->parsePositiveInt($_POST['id'] ?? null);
        if ($id === null) {
            $this->respondError('ID pengeluaran tidak valid.', 422);
            return;
        }

        if ($this->expenses->findById($id) === null) {
            $this->respondError('Data pengeluaran tidak ditemukan.', 404);
            return;
        }

        $errorMessage = null;
        $payload = $this->validateExpensePayload($_POST, $errorMessage);
        if ($payload === null) {
            $this->respondError($errorMessage ?? 'Data pengeluaran tidak valid.', 422);
            return;
        }

        try {
            $this->db->beginTransaction();

            $categoryId = $this->resolveCategoryId($payload['category']);
            if ($categoryId === null || !$this->expenses->categoryExists($categoryId)) {
                $this->db->rollBack();
                $this->respondError('Pilih kategori terlebih dahulu atau ketik kategori baru.', 422);
                return;
            }

            $this->expenses->update(
                $id,
                $categoryId,
                $payload['amount'],
                $payload['description'],
                $payload['date']
            );

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('Expense update error: ' . $exception->getMessage());
            $this->respondError('Terjadi kesalahan saat memperbarui data.', 500);
            return;
        }

        if ($this->isAjaxRequest()) {
            $this->respondJson([
                'success' => true,
                'message' => 'Pengeluaran berhasil diperbarui.',
            ]);
            return;
        }

        Session::flash('success', 'Pengeluaran berhasil diperbarui.');
        $this->redirect('/admin/expenses');
    }

    public function delete(): void
    {
        if (!$this->ensureCsrf()) {
            return;
        }

        $id = $this->parsePositiveInt($_POST['id'] ?? null);
        if ($id === null) {
            Session::flash('error', 'ID pengeluaran tidak valid.');
            $this->redirect('/admin/expenses');
        }

        if ($this->expenses->findById($id) === null) {
            Session::flash('error', 'Data pengeluaran tidak ditemukan.');
            $this->redirect('/admin/expenses');
        }

        $this->expenses->deleteById($id);
        Session::flash('success', 'Pengeluaran berhasil dihapus.');
        $this->redirect('/admin/expenses');
    }

    private function validateExpensePayload(array $input, ?string &$errorMessage = null): ?array
    {
        $category = $this->parseCategoryInput($input['category_id'] ?? null);
        if ($category === null) {
            $errorMessage = 'Pilih kategori terlebih dahulu atau ketik kategori baru.';
            return null;
        }

        $amount = $this->parseAmount($input['amount'] ?? null);
        if ($amount === null) {
            $errorMessage = 'Nominal tidak valid. Gunakan angka bulat (contoh: 50000) atau suffix (contoh: 5k, 7.5rb, 2jt). Karakter "." atau "," tanpa suffix tidak diizinkan.';
            return null;
        }

        $date = $this->parseDate($input['date'] ?? null);
        if ($date === null) {
            $errorMessage = 'Tanggal tidak valid. Gunakan format YYYY-MM-DD.';
            return null;
        }

        $description = $this->normalizeDescription($input['description'] ?? null);
        if ($description === false) {
            $errorMessage = 'Deskripsi maksimal 255 karakter.';
            return null;
        }

        return [
            'category' => $category,
            'amount' => $amount,
            'date' => $date,
            'description' => $description,
        ];
    }

    private function parseCategoryInput(mixed $raw): int|string|null
    {
        $existingId = $this->parsePositiveInt($raw);
        if ($existingId !== null) {
            return $existingId;
        }

        return $this->normalizeCategoryName($raw);
    }

    private function resolveCategoryId(mixed $raw): ?int
    {
        $existingId = $this->parsePositiveInt($raw);
        if ($existingId !== null) {
            return $existingId;
        }

        $name = $this->normalizeCategoryName($raw);
        if ($name === null) {
            return null;
        }

        $matchedId = $this->categories->findIdByName($name);
        if ($matchedId !== null) {
            return $matchedId;
        }

        try {
            return $this->categories->createAndReturnId($name);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            return $this->categories->findIdByName($name);
        }
    }

    private function ensureCsrf(): bool
    {
        $token = isset($_POST['_token']) ? (string) $_POST['_token'] : null;
        if (Csrf::validate($token)) {
            return true;
        }

        if ($this->isAjaxRequest()) {
            $this->respondJson([
                'success' => false,
                'message' => 'Token CSRF tidak valid.',
            ], 419);
            return false;
        }

        http_response_code(419);
        echo 'Token CSRF tidak valid.';

        return false;
    }

    private function parsePositiveInt(mixed $raw): ?int
    {
        if (is_int($raw)) {
            return $raw > 0 ? $raw : null;
        }

        if (!is_string($raw) || !preg_match('/^\d+$/', $raw)) {
            return null;
        }

        $value = (int) $raw;
        return $value > 0 ? $value : null;
    }

    private function parseOptionalPositiveInt(mixed $raw, ?string &$errorMessage, ?string $invalidMessage): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $value = $this->parsePositiveInt($raw);
        if ($value === null && $invalidMessage !== null && $errorMessage === null) {
            $errorMessage = $invalidMessage;
        }

        return $value;
    }

    private function parseAmount(mixed $raw): ?int
    {
        if (!is_string($raw)) {
            return null;
        }

        $value = strtolower(trim($raw));
        $value = preg_replace('/\s+/', '', $value) ?? '';
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d+(?:[.,]\d+)?)(k|rb|ribu|jt|juta)$/', $value, $matches)) {
            $numeric = (float) str_replace(',', '.', $matches[1]);
            $suffix = $matches[2];
            $multiplier = in_array($suffix, ['k', 'rb', 'ribu'], true) ? 1000.0 : 1000000.0;
            $amount = (int) round($numeric * $multiplier, 0);

            return $amount > 0 ? $amount : null;
        }

        // Tolak format angka desimal tanpa suffix karena nominal harus rupiah utuh (tanpa sen).
        if (preg_match('/^\d+[.,]\d+$/', $value)) {
            return null;
        }

        if (preg_match('/^\d+$/', $value)) {
            $amount = (int) $value;
            return $amount > 0 ? $amount : null;
        }

        return null;
    }

    private function parseDate(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        $date = trim($raw);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if (!checkdate($month, $day, $year)) {
            return null;
        }

        return $date;
    }

    private function parseOptionalDate(mixed $raw, ?string &$errorMessage, string $invalidMessage): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $date = $this->parseDate($raw);
        if ($date === null && $errorMessage === null) {
            $errorMessage = $invalidMessage;
        }

        return $date;
    }

    private function normalizeCategoryName(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        $name = strtolower(trim($raw));
        if ($name === '' || strlen($name) > 50) {
            return null;
        }

        return $name;
    }

    private function normalizeDescription(mixed $raw): string|false|null
    {
        if ($raw === null) {
            return null;
        }

        if (!is_string($raw)) {
            return false;
        }

        $description = trim($raw);
        if ($description === '') {
            return null;
        }

        if (strlen($description) > 255) {
            return false;
        }

        return $description;
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }

    private function isAjaxRequest(): bool
    {
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        if ($requestedWith === 'xmlhttprequest') {
            return true;
        }

        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        return str_contains($accept, 'application/json');
    }

    private function respondError(string $message, int $statusCode = 422): void
    {
        if ($this->isAjaxRequest()) {
            $this->respondJson([
                'success' => false,
                'message' => $message,
            ], $statusCode);
            return;
        }

        Session::flash('error', $message);
        $this->redirect('/admin/expenses');
    }

    private function respondJson(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
