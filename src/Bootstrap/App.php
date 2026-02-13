<?php
declare(strict_types=1);

namespace App\Bootstrap;

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ExpenseController;
use App\Controllers\Admin\ReportController;
use App\Controllers\WebhookController;
use App\Core\Auth;
use App\Core\Router;

final class App
{
    public static function run(string $method, string $requestUri): void
    {
        $router = new Router();

        $authController = new AuthController();
        $categoryController = new CategoryController();
        $dashboardController = new DashboardController();
        $expenseController = new ExpenseController();
        $reportController = new ReportController();
        $webhookController = new WebhookController();

        $router->get('/', static function (): void {
            header('Location: /admin/login');
            exit;
        });

        $router->get('/admin', static function () use ($dashboardController): void {
            Auth::requireAdmin();
            $dashboardController->index();
        });
        $router->get('/admin/categories', static function () use ($categoryController): void {
            Auth::requireAdmin();
            $categoryController->index();
        });
        $router->post('/admin/categories/store', static function () use ($categoryController): void {
            Auth::requireAdmin();
            $categoryController->store();
        });
        $router->post('/admin/categories/update', static function () use ($categoryController): void {
            Auth::requireAdmin();
            $categoryController->update();
        });
        $router->post('/admin/categories/delete', static function () use ($categoryController): void {
            Auth::requireAdmin();
            $categoryController->delete();
        });
        $router->get('/admin/expenses', static function () use ($expenseController): void {
            Auth::requireAdmin();
            $expenseController->index();
        });
        $router->post('/admin/expenses/store', static function () use ($expenseController): void {
            Auth::requireAdmin();
            $expenseController->store();
        });
        $router->post('/admin/expenses/update', static function () use ($expenseController): void {
            Auth::requireAdmin();
            $expenseController->update();
        });
        $router->post('/admin/expenses/delete', static function () use ($expenseController): void {
            Auth::requireAdmin();
            $expenseController->delete();
        });
        $router->get('/admin/reports/export', static function () use ($reportController): void {
            Auth::requireAdmin();
            $reportController->export();
        });
        $router->get('/admin/login', [$authController, 'showLogin']);
        $router->get('/admin/auth/google', [$authController, 'redirectToGoogle']);
        $router->get('/admin/auth/google/callback', [$authController, 'handleGoogleCallback']);
        $router->post('/admin/logout', [$authController, 'logout']);

        $router->post('/webhook', [$webhookController, 'handle']);

        $router->dispatch($method, $requestUri);
    }
}
