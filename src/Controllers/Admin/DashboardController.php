<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Infrastructure\Database\Connection;
use App\Repositories\DashboardRepository;

final class DashboardController
{
    private DashboardRepository $dashboard;

    public function __construct()
    {
        $this->dashboard = new DashboardRepository(Connection::get());
    }

    public function index(): void
    {
        $topCategories = $this->dashboard->getTopCategoriesThisMonth();
        $comparisons = $this->dashboard->getSpendingComparisons();
        $monthlySeries = $this->dashboard->getMonthlyComparisonSeries();
        $weeklySeries = $this->dashboard->getWeeklySeries(8);
        $dailySeries = $this->dashboard->getDailySeries(7);

        View::render('dashboard/index', [
            'admin' => Auth::user(),
            'success' => Session::consumeFlash('success'),
            'topCategories' => $topCategories,
            'comparisons' => $comparisons,
            'monthlySeries' => $monthlySeries,
            'weeklySeries' => $weeklySeries,
            'dailySeries' => $dailySeries,
        ]);
    }
}
