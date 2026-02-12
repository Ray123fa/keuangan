<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Infrastructure\Database\Connection;
use App\Repositories\CategoryRepository;
use PDOException;

final class CategoryController
{
    private CategoryRepository $categories;

    public function __construct()
    {
        $this->categories = new CategoryRepository(Connection::get());
    }

    public function index(): void
    {
        View::render('categories/index', [
            'categories' => $this->categories->findAllWithUsage(),
            'success' => Session::consumeFlash('success'),
            'error' => Session::consumeFlash('error'),
        ]);
    }

    public function store(): void
    {
        if (!$this->ensureCsrf()) {
            return;
        }

        $name = $this->normalizeName($_POST['name'] ?? null);
        if ($name === null) {
            Session::flash('error', 'Nama kategori wajib diisi dan maksimal 50 karakter.');
            $this->redirect('/admin/categories');
        }

        if ($this->categories->existsByName($name)) {
            Session::flash('error', 'Nama kategori sudah digunakan.');
            $this->redirect('/admin/categories');
        }

        $this->categories->create($name);
        Session::flash('success', 'Kategori baru berhasil ditambahkan.');
        $this->redirect('/admin/categories');
    }

    public function update(): void
    {
        if (!$this->ensureCsrf()) {
            return;
        }

        $id = $this->parsePositiveInt($_POST['id'] ?? null);
        $name = $this->normalizeName($_POST['name'] ?? null);
        if ($id === null || $name === null) {
            Session::flash('error', 'Data kategori tidak valid.');
            $this->redirect('/admin/categories');
        }

        $category = $this->categories->findById($id);
        if ($category === null) {
            Session::flash('error', 'Kategori tidak ditemukan.');
            $this->redirect('/admin/categories');
        }

        if ($this->categories->existsByName($name, $id)) {
            Session::flash('error', 'Nama kategori sudah digunakan.');
            $this->redirect('/admin/categories');
        }

        $this->categories->updateName($id, $name);
        Session::flash('success', 'Kategori berhasil diperbarui.');
        $this->redirect('/admin/categories');
    }

    public function delete(): void
    {
        if (!$this->ensureCsrf()) {
            return;
        }

        $id = $this->parsePositiveInt($_POST['id'] ?? null);
        if ($id === null) {
            Session::flash('error', 'ID kategori tidak valid.');
            $this->redirect('/admin/categories');
        }

        $category = $this->categories->findById($id);
        if ($category === null) {
            Session::flash('error', 'Kategori tidak ditemukan.');
            $this->redirect('/admin/categories');
        }

        try {
            $this->categories->deleteById($id);
            Session::flash('success', 'Kategori berhasil dihapus.');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                Session::flash('error', 'Kategori tidak bisa dihapus karena masih dipakai pada data pengeluaran.');
            } else {
                Session::flash('error', 'Terjadi kesalahan saat menghapus kategori.');
            }
        }

        $this->redirect('/admin/categories');
    }

    private function ensureCsrf(): bool
    {
        $token = isset($_POST['_token']) ? (string) $_POST['_token'] : null;
        if (Csrf::validate($token)) {
            return true;
        }

        http_response_code(419);
        echo 'Token CSRF tidak valid.';

        return false;
    }

    private function normalizeName(mixed $raw): ?string
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

    private function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }
}
