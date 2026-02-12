<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;

$filters = is_array($filters ?? null) ? $filters : [];
$selectedCategoryId = (int) ($filters['category_id'] ?? 0);
$startDateFilter = (string) ($filters['start_date'] ?? '');
$endDateFilter = (string) ($filters['end_date'] ?? '');

$pagination = is_array($pagination ?? null) ? $pagination : [];
$currentPage = max(1, (int) ($pagination['current_page'] ?? 1));
$perPage = max(1, (int) ($pagination['per_page'] ?? 20));
$totalItems = max(0, (int) ($pagination['total_items'] ?? count($expenses ?? [])));
$totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$fromItem = $totalItems > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
$toItem = min($totalItems, $currentPage * $perPage);

$baseQuery = array_filter([
    'category_id' => $selectedCategoryId > 0 ? (string) $selectedCategoryId : null,
    'start_date' => $startDateFilter !== '' ? $startDateFilter : null,
    'end_date' => $endDateFilter !== '' ? $endDateFilter : null,
], static fn (mixed $value): bool => $value !== null && $value !== '');

$buildExpensePageUrl = static function (int $page) use ($baseQuery): string {
    $query = array_merge($baseQuery, ['page' => max(1, $page)]);
    return '/admin/expenses?' . http_build_query($query);
};
?>
<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Manajemen Data</p>
            <h1 class="brand-display mt-1 text-3xl leading-tight text-[var(--ink)] sm:text-4xl">CRUD Pengeluaran</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--muted)]">Input tanggal dari admin disimpan sebagai awal hari (`00:00:00`) untuk menjaga konsistensi data historis.</p>
        </div>
        <button
            type="button"
            onclick="expenseModal.openCreate()"
            class="focus-ring inline-flex w-full items-center justify-center rounded-xl border border-[var(--accent)]/35 bg-[var(--surface)] px-4 py-2 text-sm font-semibold text-[var(--accent)] transition hover:-translate-y-0.5 hover:border-[var(--accent)]/65 md:w-auto"
        >
            Tambah Pengeluaran
        </button>
    </div>

    <?php require __DIR__ . '/../partials/flash.php'; ?>

    <article class="panel rounded-2xl p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="brand-display text-2xl text-[var(--ink)]">Daftar Pengeluaran</h2>
            <span class="rounded-full border border-[var(--line)] bg-[var(--surface)] px-3 py-1 text-xs font-semibold text-[var(--muted)]">
                <?php if ($totalItems > 0): ?>
                    Menampilkan <?= $fromItem ?>-<?= $toItem ?> dari <?= $totalItems ?> data
                <?php else: ?>
                    Menampilkan 0 dari 0 data
                <?php endif; ?>
            </span>
        </div>

        <form method="GET" action="/admin/expenses" class="mt-5 grid gap-3 rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-4 md:grid-cols-4 md:items-end">
            <div>
                <label for="filter-category" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]">Kategori</label>
                <select id="filter-category" name="category_id" class="focus-ring w-full rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-sm text-[var(--ink)]">
                    <option value="">Semua kategori</option>
                    <?php foreach ($categories as $category): ?>
                        <?php $categoryId = (int) ($category['id'] ?? 0); ?>
                        <option value="<?= $categoryId ?>" <?= $categoryId === $selectedCategoryId ? 'selected' : '' ?>>
                            <?= View::escape(ucfirst((string) ($category['name'] ?? ''))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="filter-start-date" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]">Tanggal Mulai</label>
                <input
                    id="filter-start-date"
                    type="date"
                    name="start_date"
                    value="<?= View::escape($startDateFilter) ?>"
                    class="focus-ring w-full rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-sm text-[var(--ink)]"
                >
            </div>

            <div>
                <label for="filter-end-date" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]">Tanggal Akhir</label>
                <input
                    id="filter-end-date"
                    type="date"
                    name="end_date"
                    value="<?= View::escape($endDateFilter) ?>"
                    class="focus-ring w-full rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-sm text-[var(--ink)]"
                >
            </div>

            <div class="flex flex-wrap gap-2 md:justify-end">
                <button type="submit" class="focus-ring rounded-xl border border-[var(--accent)]/35 bg-[var(--surface)] px-3 py-2 text-sm font-semibold text-[var(--accent)]">Terapkan</button>
                <a href="/admin/expenses" class="focus-ring rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-sm font-semibold text-[var(--ink)]">Reset</a>
            </div>
        </form>

        <div class="mt-5 space-y-3 md:hidden">
            <?php if (empty($expenses)): ?>
                <div class="rounded-2xl border border-dashed border-[var(--line)] bg-[var(--surface)] px-4 py-7 text-center text-sm text-[var(--muted)]">Belum ada data pengeluaran.</div>
            <?php endif; ?>

            <?php foreach ($expenses as $expense): ?>
                <?php
                $expenseId = (int) ($expense['id'] ?? 0);
                $currentCategoryId = (int) ($expense['category_id'] ?? 0);
                $currentDate = substr((string) ($expense['created_at'] ?? ''), 0, 10);
                $currentAmount = (string) ((int) ($expense['amount'] ?? 0));
                $currentDescription = (string) ($expense['description'] ?? '');
                ?>
                <article class="rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]">Kategori</p>
                            <p class="mt-1 text-base font-semibold text-[var(--ink)]"><?= View::escape(ucfirst((string) ($expense['category_name'] ?? ''))) ?></p>
                        </div>
                        <p class="text-sm font-bold text-[var(--ink)]">Rp<?= number_format((int) ($expense['amount'] ?? 0), 0, ',', '.') ?></p>
                    </div>

                    <div class="mt-3 grid gap-2 rounded-xl border border-[var(--line)]/70 bg-[#f8f4ec]/60 px-3 py-2 text-xs text-[var(--muted)]">
                        <p><span class="font-semibold uppercase tracking-[0.08em]">Tanggal:</span> <?= View::escape($currentDate) ?></p>
                        <p><span class="font-semibold uppercase tracking-[0.08em]">Deskripsi:</span> <?= View::escape($currentDescription !== '' ? $currentDescription : '-') ?></p>
                    </div>

                    <div class="mt-3 flex gap-2">
                        <button
                            type="button"
                            onclick="expenseModal.openEdit(<?= View::escape(json_encode([
                                'id' => $expenseId,
                                'category_id' => $currentCategoryId,
                                'amount' => $currentAmount,
                                'date' => $currentDate,
                                'description' => $currentDescription,
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)"
                            class="focus-ring w-full rounded-lg border border-[var(--line)] bg-white px-3 py-2 text-xs font-semibold text-[var(--ink)]"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            onclick="expenseModal.openDelete(<?= View::escape(json_encode([
                                'id' => $expenseId,
                                'category' => (string) ($expense['category_name'] ?? ''),
                                'amount' => number_format((int) ($expense['amount'] ?? 0), 0, ',', '.'),
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)"
                            class="focus-ring w-full rounded-lg border border-[var(--warn)]/35 bg-white px-3 py-2 text-xs font-semibold text-[var(--warn)]"
                        >
                            Hapus
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="mt-5 hidden overflow-x-auto rounded-2xl border border-[var(--line)] bg-[var(--surface)] md:block">
            <table class="w-full min-w-full text-sm">
                <thead class="border-b border-[var(--line)] bg-[#f8f4ec] text-left text-xs uppercase tracking-[0.12em] text-[var(--muted)]">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Nominal</th>
                        <th class="px-4 py-3">Deskripsi</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-[var(--muted)]">Belum ada data pengeluaran.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($expenses as $expense): ?>
                        <?php
                        $expenseId = (int) ($expense['id'] ?? 0);
                        $currentCategoryId = (int) ($expense['category_id'] ?? 0);
                        $currentDate = substr((string) ($expense['created_at'] ?? ''), 0, 10);
                        $currentAmount = (string) ((int) ($expense['amount'] ?? 0));
                        $currentDescription = (string) ($expense['description'] ?? '');
                        ?>
                        <tr class="border-b border-[var(--line)]/70 align-top last:border-b-0">
                            <td class="px-4 py-3 text-[var(--muted)]"><?= View::escape($currentDate) ?></td>
                            <td class="px-4 py-3 font-semibold text-[var(--ink)]"><?= View::escape(ucfirst((string) ($expense['category_name'] ?? ''))) ?></td>
                            <td class="px-4 py-3 font-bold text-[var(--ink)]">Rp<?= number_format((int) ($expense['amount'] ?? 0), 0, ',', '.') ?></td>
                            <td class="px-4 py-3 text-[var(--muted)]"><?= View::escape($currentDescription !== '' ? $currentDescription : '-') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        onclick="expenseModal.openEdit(<?= View::escape(json_encode([
                                            'id' => $expenseId,
                                            'category_id' => $currentCategoryId,
                                            'amount' => $currentAmount,
                                            'date' => $currentDate,
                                            'description' => $currentDescription,
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)"
                                        class="focus-ring rounded-lg border border-[var(--line)] bg-white px-3 py-1.5 text-xs font-semibold text-[var(--ink)]"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        onclick="expenseModal.openDelete(<?= View::escape(json_encode([
                                            'id' => $expenseId,
                                            'category' => (string) ($expense['category_name'] ?? ''),
                                            'amount' => number_format((int) ($expense['amount'] ?? 0), 0, ',', '.'),
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)"
                                        class="focus-ring rounded-lg border border-[var(--warn)]/35 bg-white px-3 py-1.5 text-xs font-semibold text-[var(--warn)]"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $startPage + 4);
            $startPage = max(1, $endPage - 4);
            ?>
            <nav class="mt-5 flex flex-wrap items-center justify-between gap-3" aria-label="Navigasi halaman pengeluaran">
                <p class="text-xs text-[var(--muted)]">Halaman <?= $currentPage ?> dari <?= $totalPages ?></p>
                <div class="flex flex-wrap items-center gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= View::escape($buildExpensePageUrl($currentPage - 1)) ?>" class="focus-ring rounded-lg border border-[var(--line)] bg-white px-3 py-1.5 text-xs font-semibold text-[var(--ink)]">Sebelumnya</a>
                    <?php endif; ?>

                    <?php for ($page = $startPage; $page <= $endPage; $page++): ?>
                        <?php if ($page === $currentPage): ?>
                            <span class="rounded-lg border border-[var(--accent)]/45 bg-[var(--accent-soft)] px-3 py-1.5 text-xs font-semibold text-[var(--accent)]"><?= $page ?></span>
                        <?php else: ?>
                            <a href="<?= View::escape($buildExpensePageUrl($page)) ?>" class="focus-ring rounded-lg border border-[var(--line)] bg-white px-3 py-1.5 text-xs font-semibold text-[var(--ink)]"><?= $page ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= View::escape($buildExpensePageUrl($currentPage + 1)) ?>" class="focus-ring rounded-lg border border-[var(--line)] bg-white px-3 py-1.5 text-xs font-semibold text-[var(--ink)]">Berikutnya</a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </article>
</section>

<script>
window.expenseModal = (function () {
    'use strict';

    var TOKEN = <?= json_encode(Csrf::token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var TODAY = <?= json_encode(date('Y-m-d'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var CATEGORIES = <?= json_encode(array_map(function (array $c): array {
        return ['id' => (int) ($c['id'] ?? 0), 'name' => ucfirst((string) ($c['name'] ?? ''))];
    }, $categories), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    var ui = window.KeuanganUI && window.KeuanganUI.modalCore;
    if (!ui) {
        throw new Error('Modal core tidak tersedia.');
    }

    var h = ui.h;
    var hs = ui.hs;
    var lockScroll = ui.lockScroll;
    var createOverlay = ui.createOverlay;
    var closeIcon = ui.closeIcon;

    function backspaceIcon() {
        return hs('svg', {
            xmlns: 'http://www.w3.org/2000/svg',
            viewBox: '0 0 24 24',
            width: '20',
            height: '20',
            fill: 'none',
            stroke: 'currentColor',
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            'aria-hidden': 'true'
        }, [
            hs('path', {
                d: 'M7.70015 6.35982L3.53349 11.3598C3.22445 11.7307 3.22445 12.2693 3.53349 12.6402L7.70015 17.6402C7.89015 17.8682 8.1716 18 8.46838 18H18C19.6569 18 21 16.6569 21 15V9C21 7.34315 19.6569 6 18 6H8.46837C8.1716 6 7.89015 6.13182 7.70015 6.35982Z'
            }),
            hs('path', {
                d: 'M15 10L13 12M13 12L11 14M13 12L11 10M13 12L15 14'
            })
        ]);
    }

    function calculatorIcon() {
        return hs('svg', {
            xmlns: 'http://www.w3.org/2000/svg',
            viewBox: '0 0 24 24',
            width: '18',
            height: '18',
            fill: 'none',
            stroke: 'currentColor',
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '1.5',
            'aria-hidden': 'true'
        }, [
            hs('path', {
                d: 'M3.46447 20.5355C4.92893 22 7.28595 22 12 22C16.714 22 19.0711 22 20.5355 20.5355C22 19.0711 22 16.714 22 12C22 7.28595 22 4.92893 20.5355 3.46447C19.0711 2 16.714 2 12 2C7.28595 2 4.92893 2 3.46447 3.46447C2 4.92893 2 7.28595 2 12C2 16.714 2 19.0711 3.46447 20.5355Z'
            }),
            hs('path', {
                d: 'M18 8.49998H14M18 14.5H14M18 17.5H14M10 8.49999H8M8 8.49999L6 8.49999M8 8.49999L8 6.49998M8 8.49999L8 10.5M9.5 14.5L8.00001 16M8.00001 16L6.50001 17.5M8.00001 16L6.5 14.5M8.00001 16L9.49999 17.5'
            })
        ]);
    }

    function removeModal(overlay, dialog) {
        if (dialog && dialog._choicesInstance && typeof dialog._choicesInstance.destroy === 'function') {
            dialog._choicesInstance.destroy();
        }

        ui.removeModal(overlay, dialog);
    }

    function initCategoryChoices(selectEl, isCreate) {
        if (typeof window.Choices !== 'function') {
            return null;
        }

        return new window.Choices(selectEl, {
            searchEnabled: true,
            searchChoices: true,
            shouldSort: false,
            addChoices: true,
            addItems: true,
            duplicateItemsAllowed: false,
            allowHTML: false,
            allowHtmlUserInput: false,
            itemSelectText: '',
            searchPlaceholderValue: 'Cari atau ketik kategori baru',
            noResultsText: 'Tidak ada hasil',
            noChoicesText: 'Tidak ada kategori',
            addItemText: function (value) {
                return 'Enter untuk tambah kategori baru: "' + value + '"';
            },
            placeholder: true,
            placeholderValue: isCreate ? 'Pilih atau ketik kategori' : null
        });
    }

    function buildCategorySelect(id, name, selectedId, includePlaceholder) {
        var opts = CATEGORIES.map(function (c) {
            return h('option', { value: String(c.id), selected: c.id === selectedId }, [c.name]);
        });
        if (includePlaceholder) {
            opts.unshift(h('option', {
                value: '',
                selected: selectedId === 0
            }, ['Pilih kategori']));
        }
        return h('select', {
            id: id,
            name: name,
            autocomplete: 'off',
            className: 'focus-ring w-full rounded-xl border border-[var(--line)] bg-[var(--surface)] px-3 py-2 text-sm text-[var(--ink)]'
        }, opts);
    }

    function labelEl(forId, text) {
        return h('label', {
            'for': forId,
            className: 'mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]'
        }, [text]);
    }

    function openForm(config) {
        var id = Number(config.id || 0);
        var categoryId = Number(config.categoryId || 0);
        var amount = String(config.amount || '');
        var date = String(config.date || TODAY);
        var description = String(config.description || '');
        var titleId = String(config.titleId || 'expense-form-title');
        var isCreate = Boolean(config.isCreate);
        var fieldIdSeed = 'expense-form-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
        var categoryFieldId = fieldIdSeed + '-category';
        var amountFieldId = fieldIdSeed + '-amount';
        var amountCalcToggleId = fieldIdSeed + '-amount-calc-toggle';
        var amountCalcModalId = fieldIdSeed + '-amount-calc-modal';
        var dateFieldId = fieldIdSeed + '-date';
        var descriptionFieldId = fieldIdSeed + '-description';

        var overlay, dialog;
        function close() {
            if (isCalcOpen) {
                closeCalc(true);
            } else {
                detachCalcKeyboard();
            }

            removeModal(overlay, dialog);
        }

        overlay = createOverlay(close);

        var categorySelect = buildCategorySelect(categoryFieldId, 'category_id', categoryId, isCreate);
        var amountInput = h('input', {
            id: amountFieldId, name: 'amount', type: 'text', value: amount, required: 'required', inputmode: 'text', autocomplete: 'off',
            placeholder: 'Contoh: 5k, 7.5rb, 2jt',
            className: 'focus-ring w-full rounded-xl border border-[var(--line)] bg-[var(--surface)] py-2 pl-3 pr-16 text-sm text-[var(--ink)]'
        });

        var calcExpressionEl = h('div', {
            className: 'mb-1 min-h-[1.25rem] text-right text-xs font-semibold tracking-[0.08em] text-[var(--muted)]',
            'aria-live': 'polite'
        }, ['']);
        var calcValueEl = h('div', {
            className: 'rounded-lg border border-[var(--line)]/70 bg-white px-3 py-2 text-right text-xl font-semibold text-[var(--ink)]',
            'aria-live': 'polite'
        }, ['0']);
        var calcLastOpEl = h('p', {
            className: 'mt-1 min-h-[1.25rem] text-right text-xs text-[var(--muted)]'
        }, ['']);
        var calcHintEl = h('p', {
            className: 'mt-1 text-xs text-[var(--muted)]'
        }, ['Hasil dibulatkan ke rupiah terdekat.']);

        var calcState = {
            display: '0',
            accumulator: null,
            operator: null,
            resetDisplay: false,
            hasError: false,
            message: '',
            lastOperation: ''
        };
        var operatorButtons = [];
        var calcHotkeyButtons = {};

        function formatNumber(value) {
            if (!Number.isFinite(value)) {
                return '0';
            }

            var rounded = Math.round(value * 1000000) / 1000000;
            var text = String(rounded);
            return text.indexOf('.') >= 0 ? text.replace(/\.?0+$/, '') : text;
        }

        function parseDisplayNumber() {
            return Number(calcState.display);
        }

        function formatReadable(value) {
            if (!Number.isFinite(value)) {
                return '0';
            }

            return value.toLocaleString('id-ID', {
                maximumFractionDigits: 6,
                minimumFractionDigits: 0
            });
        }

        function operatorSymbol(operator) {
            if (operator === '*') return 'x';
            if (operator === '/') return '÷';
            return operator;
        }

        function expressionPreviewText() {
            if (calcState.operator === null || calcState.accumulator === null) {
                return '';
            }

            var left = formatReadable(calcState.accumulator);
            var operator = operatorSymbol(calcState.operator);
            if (calcState.resetDisplay) {
                return left + ' ' + operator;
            }

            return left + ' ' + operator + ' ' + formatReadable(parseDisplayNumber());
        }

        function resetCalcState(nextDisplay) {
            calcState.display = nextDisplay || '0';
            calcState.accumulator = null;
            calcState.operator = null;
            calcState.resetDisplay = false;
            calcState.hasError = false;
            calcState.message = '';
            calcState.lastOperation = '';
        }

        function calculate(a, b, operator) {
            if (operator === '+') return a + b;
            if (operator === '-') return a - b;
            if (operator === '*') return a * b;
            if (operator === '/') {
                if (b === 0) {
                    return null;
                }

                return a / b;
            }

            return b;
        }

        function currentRoundedValue() {
            var current = parseDisplayNumber();
            if (!Number.isFinite(current)) {
                return null;
            }

            var rounded = Math.round(current);
            return rounded > 0 ? rounded : null;
        }

        function refreshCalcView() {
            calcExpressionEl.textContent = expressionPreviewText();
            calcValueEl.textContent = calcState.display;
            calcLastOpEl.textContent = calcState.lastOperation;

            if (calcState.hasError && calcState.message !== '') {
                calcHintEl.className = 'mt-1 text-xs text-[var(--warn)]';
                calcHintEl.textContent = calcState.message;
            } else if (calcState.operator !== null && calcState.accumulator !== null && calcState.resetDisplay) {
                calcHintEl.className = 'mt-1 text-xs text-[var(--accent)]';
                calcHintEl.textContent = 'Operator ' + operatorSymbol(calcState.operator) + ' aktif. Masukkan angka berikutnya.';
            } else {
                calcHintEl.className = 'mt-1 text-xs text-[var(--muted)]';
                calcHintEl.textContent = 'Hasil dibulatkan ke rupiah terdekat.';
            }

            operatorButtons.forEach(function (entry) {
                if (calcState.operator === entry.operator && calcState.resetDisplay) {
                    entry.button.className = 'focus-ring rounded-lg border border-[var(--accent)]/55 bg-[var(--accent-soft)] px-2 py-2 text-sm font-semibold text-[var(--accent)] transition hover:-translate-y-0.5';
                } else {
                    entry.button.className = 'focus-ring rounded-lg border border-[var(--line)] bg-white px-2 py-2 text-sm font-semibold text-[var(--ink)] transition hover:-translate-y-0.5';
                }
            });

            applyCalcBtn.disabled = currentRoundedValue() === null;
            applyCalcBtn.className = applyCalcBtn.disabled
                ? 'focus-ring rounded-lg border border-[var(--line)] bg-white px-3 py-1.5 text-xs font-semibold text-[var(--muted)] opacity-70 cursor-not-allowed'
                : 'focus-ring rounded-lg border border-[var(--accent)]/35 bg-[var(--surface)] px-3 py-1.5 text-xs font-semibold text-[var(--accent)]';
        }

        function clearCalcError() {
            if (!calcState.hasError) {
                return;
            }

            calcState.hasError = false;
            calcState.message = '';
        }

        function setCalcError(message) {
            calcState.hasError = true;
            calcState.message = message;
            calcState.display = '0';
            calcState.accumulator = null;
            calcState.operator = null;
            calcState.resetDisplay = false;
            calcState.lastOperation = '';
        }

        function pushDigit(next) {
            clearCalcError();

            if (calcState.operator === null && calcState.accumulator === null) {
                calcState.lastOperation = '';
            }

            if (calcState.resetDisplay) {
                calcState.display = next === '.' ? '0.' : next;
                calcState.resetDisplay = false;
                return;
            }

            if (next === '.') {
                if (calcState.display.indexOf('.') >= 0) {
                    return;
                }

                calcState.display += '.';
                return;
            }

            if (calcState.display === '0') {
                calcState.display = next;
                return;
            }

            calcState.display += next;
        }

        function executePendingOperation() {
            var current = parseDisplayNumber();
            if (!Number.isFinite(current)) {
                setCalcError('Angka tidak valid.');
                return;
            }

            if (calcState.accumulator === null || calcState.operator === null) {
                calcState.accumulator = current;
                return;
            }

            var result = calculate(calcState.accumulator, current, calcState.operator);
            if (result === null) {
                setCalcError('Tidak bisa dibagi 0.');
                return;
            }

            calcState.accumulator = result;
            calcState.display = formatNumber(result);
        }

        function setOperator(nextOperator) {
            clearCalcError();
            executePendingOperation();
            if (calcState.hasError) {
                return;
            }

            calcState.operator = nextOperator;
            calcState.resetDisplay = true;
        }

        function runEquals() {
            clearCalcError();
            if (calcState.operator === null || calcState.accumulator === null) {
                return;
            }

            var current = parseDisplayNumber();
            if (!Number.isFinite(current)) {
                setCalcError('Angka tidak valid.');
                return;
            }

            var result = calculate(calcState.accumulator, current, calcState.operator);
            if (result === null) {
                setCalcError('Tidak bisa dibagi 0.');
                return;
            }

            var left = calcState.accumulator;
            var operator = calcState.operator;
            var right = current;
            calcState.display = formatNumber(result);
            calcState.lastOperation = formatReadable(left) + ' ' + operatorSymbol(operator) + ' ' + formatReadable(right) + ' = ' + formatReadable(result);
            calcState.accumulator = null;
            calcState.operator = null;
            calcState.resetDisplay = true;
        }

        function runBackspace() {
            clearCalcError();
            if (calcState.resetDisplay) {
                return;
            }

            if (calcState.display.length <= 1) {
                calcState.display = '0';
                return;
            }

            calcState.display = calcState.display.slice(0, -1);
            if (calcState.display === '-' || calcState.display === '') {
                calcState.display = '0';
            }
        }

        function runCalcAction(action, value) {
            if (action === 'digit') {
                pushDigit(String(value));
            } else if (action === 'tripleZero') {
                pushDigit('0');
                pushDigit('0');
                pushDigit('0');
            } else if (action === 'operator') {
                setOperator(String(value));
            } else if (action === 'equals') {
                runEquals();
            } else if (action === 'clear') {
                resetCalcState('0');
            } else if (action === 'backspace') {
                runBackspace();
            }

            refreshCalcView();
        }

        function pulseCalcButton(button) {
            if (!button) {
                return;
            }

            button.style.transform = 'translateY(0) scale(0.98)';
            button.style.borderColor = 'rgba(201, 157, 93, 0.7)';
            button.style.backgroundColor = 'rgba(232, 206, 159, 0.32)';

            if (button._pulseTimer) {
                clearTimeout(button._pulseTimer);
            }

            button._pulseTimer = setTimeout(function () {
                button.style.transform = '';
                button.style.borderColor = '';
                button.style.backgroundColor = '';
            }, 120);
        }

        function createCalcBtn(label, action, value, extraClass, ariaLabel) {
            var buttonAttrs = {
                type: 'button',
                className: 'focus-ring inline-flex w-full items-center justify-center rounded-lg border border-[var(--line)] bg-white px-2 py-2 text-sm font-semibold text-[var(--ink)] transition hover:-translate-y-0.5 ' + (extraClass || '')
            };

            if (typeof ariaLabel === 'string' && ariaLabel !== '') {
                buttonAttrs['aria-label'] = ariaLabel;
            }

            var button = h('button', buttonAttrs, [label]);
            button.addEventListener('click', function () {
                pulseCalcButton(button);
                runCalcAction(action, value);
            });

            if (action === 'operator' && typeof value === 'string') {
                operatorButtons.push({ operator: value, button: button });
            }

            if (action === 'digit' && typeof value === 'string') {
                calcHotkeyButtons[value] = button;
                if (value === '.') {
                    calcHotkeyButtons[','] = button;
                }
            }

            if (action === 'operator' && typeof value === 'string') {
                calcHotkeyButtons[value] = button;
                if (value === '*') {
                    calcHotkeyButtons.x = button;
                    calcHotkeyButtons.X = button;
                }
            }

            if (action === 'equals') {
                calcHotkeyButtons['='] = button;
                calcHotkeyButtons.Enter = button;
            }

            if (action === 'backspace') {
                calcHotkeyButtons.Backspace = button;
            }

            if (action === 'clear') {
                calcHotkeyButtons.Delete = button;
                calcHotkeyButtons.c = button;
                calcHotkeyButtons.C = button;
            }

            if (action === 'tripleZero') {
                calcHotkeyButtons.k = button;
                calcHotkeyButtons.K = button;
            }

            return button;
        }

        var calcGrid = h('div', { className: 'mt-3 grid grid-cols-4 gap-1.5' }, [
            createCalcBtn('C', 'clear', null, 'text-[var(--warn)]'),
            createCalcBtn(backspaceIcon(), 'backspace', null, '', 'Hapus satu digit'),
            createCalcBtn('÷', 'operator', '/', ''),
            createCalcBtn('x', 'operator', '*', ''),
            createCalcBtn('7', 'digit', '7', ''),
            createCalcBtn('8', 'digit', '8', ''),
            createCalcBtn('9', 'digit', '9', ''),
            createCalcBtn('-', 'operator', '-', ''),
            createCalcBtn('4', 'digit', '4', ''),
            createCalcBtn('5', 'digit', '5', ''),
            createCalcBtn('6', 'digit', '6', ''),
            createCalcBtn('+', 'operator', '+', ''),
            createCalcBtn('1', 'digit', '1', ''),
            createCalcBtn('2', 'digit', '2', ''),
            createCalcBtn('3', 'digit', '3', ''),
            createCalcBtn('=', 'equals', null, 'text-[var(--accent)]'),
            createCalcBtn('000', 'tripleZero', null, ''),
            createCalcBtn('0', 'digit', '0', ''),
            createCalcBtn('.', 'digit', '.', 'col-span-2')
        ]);

        var applyCalcBtn = h('button', {
            type: 'button',
            className: 'focus-ring rounded-lg border border-[var(--accent)]/35 bg-[var(--surface)] px-3 py-1.5 text-xs font-semibold text-[var(--accent)]',
            onClick: function () {
                var rounded = currentRoundedValue();
                if (rounded === null) {
                    return;
                }

                amountInput.value = String(rounded);
                closeCalc();
                amountInput.focus();
            }
        }, ['Gunakan Hasil']);

        var closeCalcBtn = h('button', {
            type: 'button',
            className: 'focus-ring inline-flex h-8 w-8 items-center justify-center rounded-lg border border-[var(--line)] bg-white text-[var(--ink)]',
            'aria-label': 'Tutup kalkulator',
            onClick: function () {
                closeCalc();
            }
        }, [closeIcon()]);

        var calcModal = h('div', {
            id: amountCalcModalId,
            className: 'pointer-events-auto fixed inset-0 z-[10010] hidden items-center justify-center p-4'
        }, [
            h('button', {
                type: 'button',
                className: 'absolute inset-0 bg-[color:rgba(31,37,34,0.55)]',
                'aria-label': 'Tutup kalkulator',
                onClick: function () {
                    closeCalc();
                }
            }),
            h('div', {
                className: 'panel relative w-full max-w-xs rounded-2xl p-3',
                role: 'dialog',
                'aria-modal': 'true',
                'aria-label': 'Kalkulator nominal',
                onClick: function (event) {
                    event.stopPropagation();
                }
            }, [
                h('div', { className: 'mb-2 flex items-center justify-between' }, [
                    h('p', { className: 'text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]' }, ['Kalkulator']),
                    closeCalcBtn
                ]),
                calcExpressionEl,
                calcValueEl,
                calcLastOpEl,
                calcHintEl,
                calcGrid,
                h('div', { className: 'mt-2 flex items-center justify-end gap-1.5' }, [applyCalcBtn])
            ])
        ]);

        var calcToggleBtn = h('button', {
            id: amountCalcToggleId,
            type: 'button',
            className: 'focus-ring absolute right-1 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg bg-[var(--surface)] text-[var(--accent)]',
            'aria-label': 'Buka kalkulator nominal',
            title: 'Buka kalkulator',
            'aria-expanded': 'false',
            'aria-controls': amountCalcModalId,
            onClick: function (event) {
                event.stopPropagation();
                if (isCalcOpen) {
                    closeCalc();
                } else {
                    openCalc();
                }
            }
        }, [calculatorIcon()]);

        var amountFieldWrap = h('div', { className: 'relative' }, [amountInput, calcToggleBtn]);

        var isCalcOpen = false;
        var isCalcKeyboardAttached = false;

        function onCalcKeydown(event) {
            if (!isCalcOpen) {
                return;
            }

            if (event.metaKey || event.ctrlKey || event.altKey) {
                return;
            }

            var key = event.key;
            var action = null;
            var value = null;

            if (/^\d$/.test(key)) {
                action = 'digit';
                value = key;
            } else if (key === '.' || key === ',') {
                action = 'digit';
                value = '.';
            } else if (key === '+' || key === '-' || key === '*' || key === '/') {
                action = 'operator';
                value = key;
            } else if (key === 'x' || key === 'X') {
                action = 'operator';
                value = '*';
            } else if (key === '=' || key === 'Enter') {
                action = 'equals';
            } else if (key === 'Backspace') {
                action = 'backspace';
            } else if (key === 'Delete' || key === 'c' || key === 'C') {
                action = 'clear';
            } else if (key === 'k' || key === 'K') {
                action = 'tripleZero';
            } else if (key === 'Escape') {
                event.preventDefault();
                event.stopImmediatePropagation();
                closeCalc();
                return;
            } else {
                return;
            }

            event.preventDefault();
            runCalcAction(action, value);
            pulseCalcButton(calcHotkeyButtons[key] || calcHotkeyButtons[value] || null);
        }

        function attachCalcKeyboard() {
            if (isCalcKeyboardAttached) {
                return;
            }

            document.addEventListener('keydown', onCalcKeydown);
            isCalcKeyboardAttached = true;
        }

        function detachCalcKeyboard() {
            if (!isCalcKeyboardAttached) {
                return;
            }

            document.removeEventListener('keydown', onCalcKeydown);
            isCalcKeyboardAttached = false;
        }

        function closeCalc(skipFocus) {
            calcModal.classList.add('hidden');
            calcModal.classList.remove('flex');
            calcToggleBtn.setAttribute('aria-expanded', 'false');
            isCalcOpen = false;
            detachCalcKeyboard();
            if (!skipFocus) {
                calcToggleBtn.focus();
            }
        }

        function openCalc() {
            resetCalcState(amountInput.value && /^\d+$/.test(amountInput.value) ? amountInput.value : '0');
            refreshCalcView();
            calcModal.classList.remove('hidden');
            calcModal.classList.add('flex');
            calcToggleBtn.setAttribute('aria-expanded', 'true');
            isCalcOpen = true;
            attachCalcKeyboard();
            closeCalcBtn.focus();
        }

        var dateInput = h('input', {
            id: dateFieldId, name: 'date', type: 'date', value: date, required: 'required', autocomplete: 'off',
            className: 'focus-ring w-full rounded-xl border border-[var(--line)] bg-[var(--surface)] px-3 py-2 text-sm text-[var(--ink)]'
        });
        var descInput = h('input', {
            id: descriptionFieldId, name: 'description', type: 'text', maxlength: '255', value: description, autocomplete: 'off',
            placeholder: 'Contoh: makan siang',
            className: 'focus-ring w-full rounded-xl border border-[var(--line)] bg-[var(--surface)] px-3 py-2 text-sm text-[var(--ink)]'
        });

        var formErrorEl = h('div', {
            className: 'hidden rounded-xl border border-[#cf8571]/45 bg-[#fff4ef] px-3 py-2 text-sm text-[#9d442f]'
        }, ['']);

        function clearFormError() {
            formErrorEl.classList.add('hidden');
            formErrorEl.textContent = '';
        }

        function showFormError(message) {
            formErrorEl.textContent = String(message || 'Terjadi kesalahan. Silakan coba lagi.');
            formErrorEl.classList.remove('hidden');
        }

        var submitLabelDefault = String(config.submitLabel || 'Simpan');
        var submitSpinner = h('span', {
            className: 'hidden h-4 w-4 animate-spin rounded-full border-2 border-[var(--accent)] border-t-transparent',
            'aria-hidden': 'true'
        });
        var submitText = h('span', {}, [submitLabelDefault]);
        var submitBtn = h('button', {
            type: 'submit',
            className: 'focus-ring inline-flex items-center justify-center gap-2 rounded-xl border border-[var(--accent)]/30 bg-[var(--surface)] px-3 py-2 text-sm font-semibold text-[var(--accent)] transition'
        }, [submitSpinner, submitText]);

        function setSubmitLoading(isLoading) {
            submitBtn.disabled = isLoading;
            if (isLoading) {
                submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                submitSpinner.classList.remove('hidden');
                submitText.textContent = 'Menyimpan...';
                return;
            }

            submitBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            submitSpinner.classList.add('hidden');
            submitText.textContent = submitLabelDefault;
        }

        var expenseForm = h('form', { method: 'POST', action: config.action, className: 'mt-5 space-y-4', autocomplete: 'off', novalidate: 'novalidate' }, [
            h('input', { type: 'hidden', name: '_token', value: TOKEN }),
            config.includeId ? h('input', { type: 'hidden', name: 'id', value: String(id) }) : null,
            formErrorEl,
            h('div', {}, [
                labelEl(categoryFieldId, 'Kategori'),
                categorySelect,
                h('p', { className: 'mt-1 text-xs text-[var(--muted)]' }, ['Bisa pilih kategori yang ada atau ketik kategori baru.'])
            ]),
            h('div', {}, [ labelEl(amountFieldId, 'Nominal'), amountFieldWrap ]),
            h('div', {}, [ labelEl(dateFieldId, 'Tanggal'), dateInput ]),
            h('div', {}, [ labelEl(descriptionFieldId, 'Deskripsi (Opsional)'), descInput ]),
            h('div', { className: 'flex justify-end gap-2 pt-1' }, [
                h('button', {
                    type: 'button',
                    className: 'focus-ring rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-sm font-semibold text-[var(--ink)]',
                    onClick: close
                }, ['Batal']),
                submitBtn
            ])
        ]);

        expenseForm.addEventListener('submit', function (event) {
            event.preventDefault();
            clearFormError();

            if (submitBtn.disabled) {
                return;
            }

            setSubmitLoading(true);

            var formData = new FormData(expenseForm);

            fetch(config.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).then(function (response) {
                return response.json().catch(function () {
                    return { success: false, message: 'Respons server tidak valid.' };
                }).then(function (data) {
                    return { ok: response.ok, status: response.status, data: data };
                });
            }).then(function (result) {
                if (result.ok && result.data && result.data.success) {
                    window.location.reload();
                    return;
                }

                showFormError(result.data && result.data.message ? result.data.message : 'Gagal menyimpan data.');
            }).catch(function () {
                showFormError('Gagal terhubung ke server. Coba lagi beberapa saat.');
            }).finally(function () {
                setSubmitLoading(false);
            });
        });

        dialog = h('div', {
            className: 'fixed inset-0 z-[9999] flex items-end justify-center px-3 pb-3 pt-6 pointer-events-none sm:items-center sm:px-4 sm:pb-4',
            role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': titleId
        }, [
            h('div', { className: 'panel relative w-full max-w-2xl max-h-[88vh] overflow-y-auto rounded-2xl p-5 pointer-events-auto sm:p-6' }, [
                h('div', { className: 'flex items-start justify-between gap-4' }, [
                    h('div', {}, [
                        h('p', { className: 'text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted)]' }, [config.kicker]),
                        h('h3', { id: titleId, className: 'brand-display mt-1 text-2xl text-[var(--ink)]' }, [config.heading])
                    ]),
                    h('button', {
                        type: 'button',
                        className: 'focus-ring inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--line)] bg-white text-[var(--muted)]',
                        'aria-label': 'Tutup modal',
                        onClick: close
                    }, [closeIcon()])
                ]),
                expenseForm
            ]),
            calcModal
        ]);

        dialog._escHandler = function (e) {
            if (e.key !== 'Escape') {
                return;
            }

            if (isCalcOpen) {
                closeCalc();
                return;
            }

            close();
        };
        document.addEventListener('keydown', dialog._escHandler);

        document.body.appendChild(overlay);
        document.body.appendChild(dialog);
        lockScroll();

        categorySelect.value = categoryId > 0 ? String(categoryId) : '';
        amountInput.value = amount;
        dateInput.value = date;
        descInput.value = description;

        if (isCreate) {
            categorySelect.selectedIndex = 0;
            categorySelect.value = '';
        }

        dialog._choicesInstance = initCategoryChoices(categorySelect, isCreate);

        if (isCreate && dialog._choicesInstance) {
            dialog._choicesInstance.setChoiceByValue('');
        }

        if (!isCreate && dialog._choicesInstance && categoryId > 0) {
            dialog._choicesInstance.setChoiceByValue(String(categoryId));
        }

        if (dialog._choicesInstance && dialog._choicesInstance.input && dialog._choicesInstance.input.element) {
            dialog._choicesInstance.input.element.focus();
        } else {
            categorySelect.focus();
        }

        refreshCalcView();
    }

    function openCreate() {
        openForm({
            titleId: 'create-expense-title',
            kicker: 'Tambah Pengeluaran',
            heading: 'Input transaksi baru',
            action: '/admin/expenses/store',
            includeId: false,
            isCreate: true,
            submitLabel: 'Simpan'
        });
    }

    function openEdit(expense) {
        openForm({
            titleId: 'edit-expense-title',
            kicker: 'Edit Pengeluaran',
            heading: 'Ubah data transaksi',
            action: '/admin/expenses/update',
            includeId: true,
            isCreate: false,
            submitLabel: 'Update',
            id: Number(expense.id || 0),
            categoryId: Number(expense.category_id || 0),
            amount: String(expense.amount || ''),
            date: String(expense.date || TODAY),
            description: String(expense.description || '')
        });
    }

    /* ---- delete modal ---- */
    function openDelete(expense) {
        var id = Number(expense.id || 0);
        var category = String(expense.category || '');
        var amount = String(expense.amount || '0');

        var overlay, dialog;
        function close() { removeModal(overlay, dialog); }

        overlay = createOverlay(close);

        var confirmBtn = h('button', {
            id: 'delete-expense-confirm', type: 'submit',
            className: 'focus-ring inline-flex items-center justify-center gap-2 rounded-xl border border-[var(--warn)]/35 bg-white px-3 py-2 text-sm font-semibold text-[var(--warn)] transition'
        }, [
            h('span', {
                className: 'hidden h-4 w-4 animate-spin rounded-full border-2 border-[var(--warn)] border-t-transparent',
                'aria-hidden': 'true'
            }),
            h('span', {}, ['Ya, Hapus'])
        ]);

        var deleteSpinner = confirmBtn.firstChild;
        var deleteText = confirmBtn.lastChild;

        function setDeleteLoading(isLoading) {
            confirmBtn.disabled = isLoading;
            if (isLoading) {
                confirmBtn.classList.add('opacity-70', 'cursor-not-allowed');
                deleteSpinner.classList.remove('hidden');
                deleteText.textContent = 'Menghapus...';
                return;
            }

            confirmBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            deleteSpinner.classList.add('hidden');
            deleteText.textContent = 'Ya, Hapus';
        }

        var deleteForm = h('form', { method: 'POST', action: '/admin/expenses/delete', className: 'mt-5 flex justify-end gap-2' }, [
            h('input', { type: 'hidden', name: '_token', value: TOKEN }),
            h('input', { type: 'hidden', name: 'id', value: String(id) }),
            h('button', {
                type: 'button',
                className: 'focus-ring rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-sm font-semibold text-[var(--ink)]',
                onClick: close
            }, ['Batal']),
            confirmBtn
        ]);

        deleteForm.addEventListener('submit', function () {
            if (confirmBtn.disabled) {
                return;
            }

            setDeleteLoading(true);
        });

        dialog = h('div', {
            className: 'fixed inset-0 z-[9999] flex items-end justify-center px-3 pb-3 pt-6 pointer-events-none sm:items-center sm:px-4 sm:pb-4',
            role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': 'delete-expense-title'
        }, [
            h('div', { className: 'panel relative w-full max-w-md max-h-[88vh] overflow-y-auto rounded-2xl p-5 pointer-events-auto sm:p-6' }, [
                h('p', { className: 'text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted)]' }, ['Konfirmasi Hapus']),
                h('h3', { id: 'delete-expense-title', className: 'brand-display mt-1 text-2xl text-[var(--ink)]' }, ['Hapus pengeluaran ini?']),
                h('p', { className: 'mt-3 text-sm text-[var(--muted)]' }, [
                    'Data ',
                    h('span', { className: 'font-semibold text-[var(--ink)]' }, [category]),
                    ' senilai ',
                    h('span', { className: 'font-semibold text-[var(--ink)]' }, ['Rp' + amount]),
                    ' akan dihapus permanen.'
                ]),
                deleteForm
            ])
        ]);

        dialog._escHandler = function (e) { if (e.key === 'Escape') close(); };
        document.addEventListener('keydown', dialog._escHandler);

        document.body.appendChild(overlay);
        document.body.appendChild(dialog);
        lockScroll();
        confirmBtn.focus();
    }

    return { openCreate: openCreate, openEdit: openEdit, openDelete: openDelete };
})();
</script>
