<?php
declare(strict_types=1);

use App\Core\View;

$comparisons = is_array($comparisons ?? null) ? $comparisons : [];
$dailyComparison = is_array($comparisons['daily'] ?? null) ? $comparisons['daily'] : [];
$monthlyComparison = is_array($comparisons['monthly'] ?? null) ? $comparisons['monthly'] : [];
$weeklyComparison = is_array($comparisons['weekly'] ?? null) ? $comparisons['weekly'] : [];
$yearlyComparison = is_array($comparisons['yearly'] ?? null) ? $comparisons['yearly'] : [];

$series = is_array($monthlySeries ?? null) ? $monthlySeries : [];
$chartLabels = is_array($series['labels'] ?? null) ? $series['labels'] : [];
$chartCurrentYear = is_array($series['current_year'] ?? null) ? $series['current_year'] : [];
$chartPreviousYear = is_array($series['previous_year'] ?? null) ? $series['previous_year'] : [];
$chartCurrentYearLabel = (string) ($series['current_year_label'] ?? date('Y'));
$chartPreviousYearLabel = (string) ($series['previous_year_label'] ?? (string) ((int) date('Y') - 1));

$weeklySeriesData = is_array($weeklySeries ?? null) ? $weeklySeries : [];
$weeklyChartLabels = is_array($weeklySeriesData['labels'] ?? null) ? $weeklySeriesData['labels'] : [];
$weeklyChartValues = is_array($weeklySeriesData['values'] ?? null) ? $weeklySeriesData['values'] : [];
$weeklyWindowLabel = (string) ($weeklySeriesData['window_label'] ?? '8 minggu terakhir');

$dailySeriesData = is_array($dailySeries ?? null) ? $dailySeries : [];
$dailyChartLabels = is_array($dailySeriesData['labels'] ?? null) ? $dailySeriesData['labels'] : [];
$dailyChartValues = is_array($dailySeriesData['values'] ?? null) ? $dailySeriesData['values'] : [];
$dailyWindowLabel = (string) ($dailySeriesData['window_label'] ?? '7 hari terakhir');

$dailyPercent = isset($dailyComparison['percent']) && is_numeric($dailyComparison['percent']) ? (float) $dailyComparison['percent'] : null;
$monthlyPercent = isset($monthlyComparison['percent']) && is_numeric($monthlyComparison['percent']) ? (float) $monthlyComparison['percent'] : null;
$weeklyPercent = isset($weeklyComparison['percent']) && is_numeric($weeklyComparison['percent']) ? (float) $weeklyComparison['percent'] : null;
$yearlyPercent = isset($yearlyComparison['percent']) && is_numeric($yearlyComparison['percent']) ? (float) $yearlyComparison['percent'] : null;

$dailyTrend = (string) ($dailyComparison['trend'] ?? 'flat');
$monthlyTrend = (string) ($monthlyComparison['trend'] ?? 'flat');
$weeklyTrend = (string) ($weeklyComparison['trend'] ?? 'flat');
$yearlyTrend = (string) ($yearlyComparison['trend'] ?? 'flat');

$dailyDiff = (int) ($dailyComparison['diff'] ?? 0);
$monthlyDiff = (int) ($monthlyComparison['diff'] ?? 0);
$weeklyDiff = (int) ($weeklyComparison['diff'] ?? 0);
$yearlyDiff = (int) ($yearlyComparison['diff'] ?? 0);

$dailyTrendColor = $dailyTrend === 'up' ? 'text-[var(--accent)]' : ($dailyTrend === 'down' ? 'text-[var(--warn)]' : 'text-[var(--muted)]');
$monthlyTrendColor = $monthlyTrend === 'up' ? 'text-[var(--accent)]' : ($monthlyTrend === 'down' ? 'text-[var(--warn)]' : 'text-[var(--muted)]');
$weeklyTrendColor = $weeklyTrend === 'up' ? 'text-[var(--accent)]' : ($weeklyTrend === 'down' ? 'text-[var(--warn)]' : 'text-[var(--muted)]');
$yearlyTrendColor = $yearlyTrend === 'up' ? 'text-[var(--accent)]' : ($yearlyTrend === 'down' ? 'text-[var(--warn)]' : 'text-[var(--muted)]');

$dailyTrendLabel = $dailyTrend === 'up' ? 'Naik' : ($dailyTrend === 'down' ? 'Turun' : 'Stabil');
$monthlyTrendLabel = $monthlyTrend === 'up' ? 'Naik' : ($monthlyTrend === 'down' ? 'Turun' : 'Stabil');
$weeklyTrendLabel = $weeklyTrend === 'up' ? 'Naik' : ($weeklyTrend === 'down' ? 'Turun' : 'Stabil');
$yearlyTrendLabel = $yearlyTrend === 'up' ? 'Naik' : ($yearlyTrend === 'down' ? 'Turun' : 'Stabil');

$dailyPercentLabel = $dailyPercent === null ? 'N/A' : number_format(abs($dailyPercent), 1, ',', '.') . '%';
$monthlyPercentLabel = $monthlyPercent === null ? 'N/A' : number_format(abs($monthlyPercent), 1, ',', '.') . '%';
$weeklyPercentLabel = $weeklyPercent === null ? 'N/A' : number_format(abs($weeklyPercent), 1, ',', '.') . '%';
$yearlyPercentLabel = $yearlyPercent === null ? 'N/A' : number_format(abs($yearlyPercent), 1, ',', '.') . '%';

$dailyCurrent = (int) ($dailyComparison['current'] ?? 0);
$dailyPrevious = (int) ($dailyComparison['previous'] ?? 0);
$monthlyCurrent = (int) ($monthlyComparison['current'] ?? 0);
$monthlyPrevious = (int) ($monthlyComparison['previous'] ?? 0);
$weeklyCurrent = (int) ($weeklyComparison['current'] ?? 0);
$weeklyPrevious = (int) ($weeklyComparison['previous'] ?? 0);
$yearlyCurrent = (int) ($yearlyComparison['current'] ?? 0);
$yearlyPrevious = (int) ($yearlyComparison['previous'] ?? 0);

$monthTotal = $monthlyCurrent;
?>
<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Editorial Finance</p>
            <h1 class="brand-display mt-1 text-3xl leading-tight text-[var(--ink)] sm:text-4xl">Dashboard Pengeluaran</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--muted)]">Satu layar untuk membaca ritme spending harian, tren bulan berjalan, dan kategori yang paling mendominasi biaya.</p>
        </div>
        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-[var(--line)] bg-[var(--surface)] px-3 py-1 text-xs font-semibold text-[var(--muted)]">
            <span class="h-2 w-2 rounded-full bg-[var(--accent)]"></span>
            Sinkron dari chatbot WhatsApp
        </span>
    </div>

    <?php require __DIR__ . '/../partials/flash.php'; ?>

    <div class="grid gap-4 lg:grid-cols-4">
        <article class="panel rounded-2xl p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted)]">Perbandingan Tahunan</p>
            <p class="mt-2 text-xl font-bold text-[var(--ink)]">
                Rp<?= number_format($yearlyCurrent, 0, ',', '.') ?>
            </p>
            <p class="mt-1 text-xs text-[var(--muted)]">vs total tahun lalu: Rp<?= number_format($yearlyPrevious, 0, ',', '.') ?></p>
            <p class="mt-3 text-sm font-semibold <?= $yearlyTrendColor ?>">
                <?= $yearlyTrendLabel ?> <?= $yearlyPercentLabel ?>
                (Rp<?= ($yearlyDiff >= 0 ? '+' : '-') . number_format(abs($yearlyDiff), 0, ',', '.') ?>)
            </p>
        </article>

        <article class="panel rounded-2xl p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted)]">Perbandingan Bulanan</p>
            <p class="mt-2 text-xl font-bold text-[var(--ink)]">
                Rp<?= number_format($monthlyCurrent, 0, ',', '.') ?>
            </p>
            <p class="mt-1 text-xs text-[var(--muted)]">vs bulan lalu: Rp<?= number_format($monthlyPrevious, 0, ',', '.') ?></p>
            <p class="mt-3 text-sm font-semibold <?= $monthlyTrendColor ?>">
                <?= $monthlyTrendLabel ?> <?= $monthlyPercentLabel ?>
                (Rp<?= ($monthlyDiff >= 0 ? '+' : '-') . number_format(abs($monthlyDiff), 0, ',', '.') ?>)
            </p>
        </article>

        <article class="panel rounded-2xl p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted)]">Perbandingan Mingguan</p>
            <p class="mt-2 text-xl font-bold text-[var(--ink)]">
                Rp<?= number_format($weeklyCurrent, 0, ',', '.') ?>
            </p>
            <p class="mt-1 text-xs text-[var(--muted)]">vs minggu lalu: Rp<?= number_format($weeklyPrevious, 0, ',', '.') ?></p>
            <p class="mt-3 text-sm font-semibold <?= $weeklyTrendColor ?>">
                <?= $weeklyTrendLabel ?> <?= $weeklyPercentLabel ?>
                (Rp<?= ($weeklyDiff >= 0 ? '+' : '-') . number_format(abs($weeklyDiff), 0, ',', '.') ?>)
            </p>
        </article>

        <article class="panel rounded-2xl p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted)]">Perbandingan Harian</p>
            <p class="mt-2 text-xl font-bold text-[var(--ink)]">
                Rp<?= number_format($dailyCurrent, 0, ',', '.') ?>
            </p>
            <p class="mt-1 text-xs text-[var(--muted)]">vs kemarin: Rp<?= number_format($dailyPrevious, 0, ',', '.') ?></p>
            <p class="mt-3 text-sm font-semibold <?= $dailyTrendColor ?>">
                <?= $dailyTrendLabel ?> <?= $dailyPercentLabel ?>
                (Rp<?= ($dailyDiff >= 0 ? '+' : '-') . number_format(abs($dailyDiff), 0, ',', '.') ?>)
            </p>
        </article>
    </div>

    <article id="trend-panel" class="panel rounded-2xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="brand-display text-2xl text-[var(--ink)]">Tren Pengeluaran</h2>
                <p id="trend-subtitle" class="mt-1 text-sm text-[var(--muted)]">Total pengeluaran <?= View::escape($dailyWindowLabel) ?>.</p>
            </div>
            <div class="inline-flex rounded-xl border border-[var(--line)] bg-[var(--surface)] p-1" role="tablist" aria-label="Pilih periode tren">
                <button type="button" data-period="daily" class="trend-switch focus-ring rounded-lg px-3 py-1.5 text-xs font-semibold transition bg-[var(--accent-soft)] text-[var(--accent)]" aria-selected="true">Harian</button>
                <button type="button" data-period="weekly" class="trend-switch focus-ring rounded-lg px-3 py-1.5 text-xs font-semibold text-[var(--muted)] transition" aria-selected="false">Mingguan</button>
                <button type="button" data-period="monthly" class="trend-switch focus-ring rounded-lg px-3 py-1.5 text-xs font-semibold text-[var(--muted)] transition" aria-selected="false">Bulanan</button>
            </div>
        </div>
        <div class="mt-5 h-[320px] rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-3 sm:p-4">
            <canvas id="trendChartUnified" aria-label="Grafik tren pengeluaran" role="img"></canvas>
        </div>
    </article>

    <article class="panel rounded-2xl p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="brand-display text-2xl text-[var(--ink)]">Top Kategori Bulan Ini</h2>
                <p class="mt-1 text-sm text-[var(--muted)]">Prioritas pengeluaran berdasarkan nilai terbesar pada periode bulan ini.</p>
            </div>
            <span class="rounded-full border border-[var(--line)] bg-[var(--surface)] px-3 py-1 text-xs font-semibold text-[var(--muted)]">Top <?= count($topCategories) ?> kategori</span>
        </div>

        <?php if (empty($topCategories)): ?>
            <div class="mt-5 rounded-2xl border border-dashed border-[var(--line)] bg-[var(--surface)] px-5 py-8 text-center">
                <p class="text-sm font-semibold text-[var(--ink)]">Belum ada data pengeluaran bulan ini</p>
                <p class="mt-1 text-xs text-[var(--muted)]">Setelah transaksi masuk, daftar kategori otomatis tampil di sini.</p>
            </div>
        <?php else: ?>
            <div class="mt-5 space-y-3 md:hidden">
                <?php foreach ($topCategories as $index => $category): ?>
                    <?php
                    $categoryTotal = (int) ($category['total'] ?? 0);
                    $share = $monthTotal > 0 ? ($categoryTotal / $monthTotal) * 100 : 0;
                    $progress = max(0.0, min(100.0, $share));
                    ?>
                    <article class="rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]">Peringkat #<?= (int) $index + 1 ?></p>
                                <p class="mt-1 text-base font-semibold text-[var(--ink)]"><?= View::escape(ucfirst((string) $category['name'])) ?></p>
                            </div>
                            <span class="text-sm font-bold text-[var(--ink)]">Rp<?= number_format($categoryTotal, 0, ',', '.') ?></span>
                        </div>

                        <div class="mt-3 h-2.5 w-full rounded-full bg-[#ece5d9]">
                            <div class="h-2.5 rounded-full bg-[var(--accent)] transition-all duration-500" style="width: <?= number_format($progress, 1, '.', '') ?>%"></div>
                        </div>
                        <p class="mt-1 text-xs text-[var(--muted)]"><?= number_format($share, 1, ',', '.') ?>% dari total bulan ini</p>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="mt-5 hidden overflow-x-auto rounded-2xl border border-[var(--line)] bg-[var(--surface)] md:block">
                <table class="w-full min-w-full text-sm">
                    <thead class="border-b border-[var(--line)] bg-[#f8f4ec] text-left text-xs uppercase tracking-[0.12em] text-[var(--muted)]">
                        <tr>
                            <th class="px-4 py-3">Posisi</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Kontribusi</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCategories as $index => $category): ?>
                            <?php
                            $categoryTotal = (int) ($category['total'] ?? 0);
                            $share = $monthTotal > 0 ? ($categoryTotal / $monthTotal) * 100 : 0;
                            $progress = max(0.0, min(100.0, $share));
                            ?>
                            <tr class="border-b border-[var(--line)]/70 last:border-b-0">
                                <td class="px-4 py-3">
                                    <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-[#efe7d9] px-2 py-1 text-xs font-bold text-[var(--ink)]">#<?= (int) $index + 1 ?></span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-[var(--ink)]"><?= View::escape(ucfirst((string) $category['name'])) ?></td>
                                <td class="px-4 py-3">
                                    <div class="h-2.5 w-full rounded-full bg-[#ece5d9]">
                                        <div class="h-2.5 rounded-full bg-[var(--accent)] transition-all duration-500" style="width: <?= number_format($progress, 1, '.', '') ?>%"></div>
                                    </div>
                                    <p class="mt-1 text-xs text-[var(--muted)]"><?= number_format($share, 1, ',', '.') ?>% dari total bulan ini</p>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-[var(--ink)]">Rp<?= number_format($categoryTotal, 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    if (typeof window.Chart !== 'function') {
        return;
    }

    var chartEl = document.getElementById('trendChartUnified');
    var subtitleEl = document.getElementById('trend-subtitle');
    var panelEl = document.getElementById('trend-panel');
    var switchButtons = Array.prototype.slice.call(document.querySelectorAll('.trend-switch'));

    if (!chartEl || !subtitleEl || switchButtons.length === 0) {
        return;
    }

    var monthlyLabels = <?= json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var monthlyCurrentSeries = <?= json_encode($chartCurrentYear, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var monthlyPreviousSeries = <?= json_encode($chartPreviousYear, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var monthlyCurrentLabel = <?= json_encode($chartCurrentYearLabel, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var monthlyPreviousLabel = <?= json_encode($chartPreviousYearLabel, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    var weeklyLabels = <?= json_encode($weeklyChartLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var weeklyValues = <?= json_encode($weeklyChartValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var weeklyWindowLabel = <?= json_encode($weeklyWindowLabel, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    var dailyLabels = <?= json_encode($dailyChartLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var dailyValues = <?= json_encode($dailyChartValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var dailyWindowLabel = <?= json_encode($dailyWindowLabel, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    var periods = ['daily', 'weekly', 'monthly'];
    var trendConfigs = {
        daily: {
            subtitle: 'Total pengeluaran ' + dailyWindowLabel + '.',
            labels: dailyLabels,
            datasets: [
                {
                    label: 'Harian (' + dailyWindowLabel + ')',
                    data: dailyValues,
                    borderColor: '#5f7fa3',
                    backgroundColor: 'rgba(95, 127, 163, 0.15)',
                    pointBackgroundColor: '#5f7fa3',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                    tension: 0.25,
                    fill: true
                }
            ]
        },
        weekly: {
            subtitle: 'Total pengeluaran ' + weeklyWindowLabel + ' (Senin-Minggu).',
            labels: weeklyLabels,
            datasets: [
                {
                    label: 'Mingguan (' + weeklyWindowLabel + ')',
                    data: weeklyValues,
                    borderColor: '#b68654',
                    backgroundColor: 'rgba(182, 134, 84, 0.15)',
                    pointBackgroundColor: '#b68654',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                    tension: 0.25,
                    fill: true
                }
            ]
        },
        monthly: {
            subtitle: 'Perbandingan total pengeluaran bulanan ' + monthlyCurrentLabel + ' vs ' + monthlyPreviousLabel + '.',
            labels: monthlyLabels,
            datasets: [
                {
                    label: monthlyCurrentLabel,
                    data: monthlyCurrentSeries,
                    borderColor: '#7f9367',
                    backgroundColor: 'rgba(127, 147, 103, 0.16)',
                    pointBackgroundColor: '#7f9367',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true
                },
                {
                    label: monthlyPreviousLabel,
                    data: monthlyPreviousSeries,
                    borderColor: '#cf8571',
                    backgroundColor: 'rgba(207, 133, 113, 0.08)',
                    pointBackgroundColor: '#cf8571',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: false,
                    borderDash: [6, 5]
                }
            ]
        }
    };

    var chart = new window.Chart(chartEl, {
        type: 'line',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        boxHeight: 12,
                        color: '#59625b',
                        font: {
                            family: 'Plus Jakarta Sans',
                            size: 12,
                            weight: '600'
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            var amount = Number(context.parsed.y || 0);
                            return context.dataset.label + ': Rp' + amount.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: 'rgba(167, 157, 141, 0.15)'
                    },
                    ticks: {
                        color: '#7d857d'
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(167, 157, 141, 0.2)'
                    },
                    ticks: {
                        color: '#7d857d',
                        callback: function (value) {
                            return 'Rp' + Number(value).toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });

    function setActiveButton(period) {
        switchButtons.forEach(function (button) {
            var isActive = button.getAttribute('data-period') === period;
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            button.classList.toggle('bg-[var(--accent-soft)]', isActive);
            button.classList.toggle('text-[var(--accent)]', isActive);
            button.classList.toggle('text-[var(--muted)]', !isActive);
        });
    }

    function setTrend(period) {
        if (!trendConfigs[period]) {
            return;
        }

        var cfg = trendConfigs[period];
        subtitleEl.textContent = cfg.subtitle;
        chart.data.labels = cfg.labels;
        chart.data.datasets = cfg.datasets;
        chart.update();
        setActiveButton(period);

        try {
            window.localStorage.setItem('dashboard_trend_period', period);
        } catch (e) {
            // ignore storage errors
        }
    }

    switchButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setTrend(button.getAttribute('data-period'));
        });
    });

    if (panelEl) {
        var touchStartX = 0;

        panelEl.addEventListener('touchstart', function (event) {
            if (!event.changedTouches || event.changedTouches.length === 0) {
                return;
            }

            touchStartX = event.changedTouches[0].clientX;
        }, { passive: true });

        panelEl.addEventListener('touchend', function (event) {
            if (!event.changedTouches || event.changedTouches.length === 0) {
                return;
            }

            var deltaX = event.changedTouches[0].clientX - touchStartX;
            if (Math.abs(deltaX) < 40) {
                return;
            }

            var currentPeriod = switchButtons.find(function (button) {
                return button.getAttribute('aria-selected') === 'true';
            });
            var currentIndex = periods.indexOf(currentPeriod ? currentPeriod.getAttribute('data-period') : 'daily');
            var nextIndex = deltaX < 0 ? Math.min(periods.length - 1, currentIndex + 1) : Math.max(0, currentIndex - 1);

            setTrend(periods[nextIndex]);
        }, { passive: true });
    }

    var initialPeriod = 'daily';
    try {
        var savedPeriod = window.localStorage.getItem('dashboard_trend_period');
        if (savedPeriod && trendConfigs[savedPeriod]) {
            initialPeriod = savedPeriod;
        }
    } catch (e) {
        // ignore storage errors
    }

    setTrend(initialPeriod);
})();
</script>
