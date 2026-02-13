<?php
declare(strict_types=1);

use App\Core\Csrf;
use App\Core\View;

$csrfToken = View::escape(Csrf::token());
?>
<section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Manajemen Data</p>
            <h1 class="brand-display mt-1 text-3xl leading-tight text-[var(--ink)] sm:text-4xl">CRUD Kategori</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--muted)]">Kelola kategori yang dipakai untuk pencatatan pengeluaran.</p>
        </div>
    </div>

    <?php require __DIR__ . '/../partials/flash.php'; ?>

    <article class="panel rounded-2xl p-5 sm:p-6">
        <h2 class="brand-display text-2xl text-[var(--ink)]">Tambah Kategori</h2>
        <form method="POST" action="/admin/categories/store" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <input type="hidden" name="_token" value="<?= $csrfToken ?>">
            <div class="w-full sm:max-w-sm">
                <label for="name" class="mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]">Nama Kategori</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    maxlength="50"
                    required
                    class="focus-ring w-full rounded-xl border border-[var(--line)] bg-[var(--surface)] px-3 py-2 text-sm text-[var(--ink)]"
                    placeholder="Contoh: kopi"
                >
            </div>
            <button type="submit" class="focus-ring w-full rounded-xl border border-[var(--accent)]/30 bg-[var(--surface)] px-4 py-2 text-sm font-semibold text-[var(--accent)] transition hover:-translate-y-0.5 hover:border-[var(--accent)]/60 sm:w-auto">
                Simpan
            </button>
        </form>
    </article>

    <article class="panel rounded-2xl p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="brand-display text-2xl text-[var(--ink)]">Daftar Kategori</h2>
            <span class="rounded-full border border-[var(--line)] bg-[var(--surface)] px-3 py-1 text-xs font-semibold text-[var(--muted)]">
                Total <?= count($categories) ?> kategori
            </span>
        </div>

        <div class="mt-5 space-y-3 md:hidden">
            <?php foreach ($categories as $category): ?>
                <article class="rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]">Kategori</p>
                            <p class="mt-1 text-base font-semibold text-[var(--ink)]"><?= View::escape(ucfirst((string) ($category['name'] ?? ''))) ?></p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between rounded-xl border border-[var(--line)]/70 bg-[#f8f4ec]/60 px-3 py-2">
                        <span class="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]">Transaksi</span>
                        <span class="text-sm font-semibold text-[var(--ink)]"><?= number_format((int) ($category['expense_count'] ?? 0), 0, ',', '.') ?></span>
                    </div>

                    <div class="mt-3 flex gap-2">
                        <button
                            type="button"
                            onclick="categoryModal.openEdit(<?= View::escape(json_encode([
                                'id' => (int) ($category['id'] ?? 0),
                                'name' => (string) ($category['name'] ?? ''),
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)"
                            class="focus-ring w-full rounded-lg border border-[var(--line)] bg-white px-3 py-2 text-xs font-semibold text-[var(--ink)]"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            onclick="categoryModal.openDelete(<?= View::escape(json_encode([
                                'id' => (int) ($category['id'] ?? 0),
                                'name' => (string) ($category['name'] ?? ''),
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
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Transaksi</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr class="border-b border-[var(--line)]/70 align-top last:border-b-0">
                            <td class="px-4 py-3 font-semibold text-[var(--ink)]"><?= View::escape(ucfirst((string) ($category['name'] ?? ''))) ?></td>
                            <td class="px-4 py-3 text-[var(--muted)]"><?= number_format((int) ($category['expense_count'] ?? 0), 0, ',', '.') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        onclick="categoryModal.openEdit(<?= View::escape(json_encode([
                                            'id' => (int) ($category['id'] ?? 0),
                                            'name' => (string) ($category['name'] ?? ''),
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) ?>)"
                                        class="focus-ring rounded-lg border border-[var(--line)] bg-white px-3 py-1.5 text-xs font-semibold text-[var(--ink)]"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        onclick="categoryModal.openDelete(<?= View::escape(json_encode([
                                            'id' => (int) ($category['id'] ?? 0),
                                            'name' => (string) ($category['name'] ?? ''),
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
    </article>
</section>

<script>
window.categoryModal = (function () {
    'use strict';

    var TOKEN = <?= json_encode(Csrf::token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    var ui = window.KeuanganUI && window.KeuanganUI.modalCore;
    if (!ui) {
        throw new Error('Modal core tidak tersedia.');
    }

    var h = ui.h;
    var lockScroll = ui.lockScroll;
    var createOverlay = ui.createOverlay;
    var closeIcon = ui.closeIcon;

    function removeModal(overlay, dialog) {
        ui.removeModal(overlay, dialog);
    }

    /* ---- edit modal ---- */
    function openEdit(category) {
        var id = Number(category.id || 0);
        var name = String(category.name || '');

        var overlay, dialog;
        function close() { removeModal(overlay, dialog); }

        overlay = createOverlay(close);

        var nameInput = h('input', {
            id: 'edit-category-name',
            name: 'name',
            type: 'text',
            maxlength: '50',
            required: 'required',
            value: name,
            className: 'focus-ring w-full rounded-xl border border-[var(--line)] bg-[var(--surface)] px-3 py-2 text-sm text-[var(--ink)]'
        });

        var titleEl = h('h3', {
            id: 'edit-category-title',
            className: 'brand-display mt-1 text-2xl text-[var(--ink)]'
        }, [name || 'Kategori']);

        var updateSpinner = h('span', {
            className: 'hidden h-4 w-4 animate-spin rounded-full border-2 border-[var(--accent)] border-t-transparent',
            'aria-hidden': 'true'
        });
        var updateText = h('span', {}, ['Update']);
        var updateBtn = h('button', {
            type: 'submit',
            className: 'focus-ring inline-flex items-center justify-center gap-2 rounded-xl border border-[var(--accent)]/30 bg-[var(--surface)] px-3 py-2 text-sm font-semibold text-[var(--accent)] transition'
        }, [updateSpinner, updateText]);

        function setUpdateLoading(isLoading) {
            updateBtn.disabled = isLoading;
            if (isLoading) {
                updateBtn.classList.add('opacity-70', 'cursor-not-allowed');
                updateSpinner.classList.remove('hidden');
                updateText.textContent = 'Menyimpan...';
                return;
            }

            updateBtn.classList.remove('opacity-70', 'cursor-not-allowed');
            updateSpinner.classList.add('hidden');
            updateText.textContent = 'Update';
        }

        var editForm = h('form', { method: 'POST', action: '/admin/categories/update', className: 'mt-5 space-y-4' }, [
            h('input', { type: 'hidden', name: '_token', value: TOKEN }),
            h('input', { type: 'hidden', name: 'id', value: String(id) }),
            h('div', {}, [
                h('label', {
                    'for': 'edit-category-name',
                    className: 'mb-1 block text-xs font-semibold uppercase tracking-[0.12em] text-[var(--muted)]'
                }, ['Nama Kategori']),
                nameInput
            ]),
            h('div', { className: 'flex justify-end gap-2' }, [
                h('button', {
                    type: 'button',
                    className: 'focus-ring rounded-xl border border-[var(--line)] bg-white px-3 py-2 text-sm font-semibold text-[var(--ink)]',
                    onClick: close
                }, ['Batal']),
                updateBtn
            ])
        ]);

        editForm.addEventListener('submit', function () {
            if (updateBtn.disabled) {
                return;
            }

            setUpdateLoading(true);
        });

        dialog = h('div', {
            className: 'fixed inset-0 z-[9999] flex items-center justify-center p-3 pointer-events-none sm:p-4',
            role: 'dialog',
            'aria-modal': 'true',
            'aria-labelledby': 'edit-category-title'
        }, [
            h('div', { className: 'panel relative w-full max-w-md max-h-[88vh] overflow-y-auto rounded-2xl p-5 pointer-events-auto sm:p-6' }, [
                h('div', { className: 'flex items-start justify-between gap-4' }, [
                    h('div', {}, [
                        h('p', { className: 'text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted)]' }, ['Edit Kategori']),
                        titleEl
                    ]),
                    h('button', {
                        type: 'button',
                        className: 'focus-ring inline-flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--line)] bg-white text-[var(--muted)]',
                        'aria-label': 'Tutup modal',
                        onClick: close
                    }, [closeIcon()])
                ]),
                editForm
            ])
        ]);

        dialog._escHandler = function (e) { if (e.key === 'Escape') close(); };
        document.addEventListener('keydown', dialog._escHandler);

        document.body.appendChild(overlay);
        document.body.appendChild(dialog);
        lockScroll();
        nameInput.focus();
    }

    /* ---- delete modal ---- */
    function openDelete(category) {
        var id = Number(category.id || 0);
        var name = String(category.name || '');

        var overlay, dialog;
        function close() { removeModal(overlay, dialog); }

        overlay = createOverlay(close);

        var confirmBtn = h('button', {
            id: 'delete-category-confirm',
            type: 'submit',
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

        var deleteForm = h('form', { method: 'POST', action: '/admin/categories/delete', className: 'mt-5 flex justify-end gap-2' }, [
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
            className: 'fixed inset-0 z-[9999] flex items-center justify-center p-3 pointer-events-none sm:p-4',
            role: 'dialog',
            'aria-modal': 'true',
            'aria-labelledby': 'delete-category-title'
        }, [
            h('div', { className: 'panel relative w-full max-w-md max-h-[88vh] overflow-y-auto rounded-2xl p-5 pointer-events-auto sm:p-6' }, [
                h('p', { className: 'text-xs font-semibold uppercase tracking-[0.16em] text-[var(--muted)]' }, ['Konfirmasi Hapus']),
                h('h3', {
                    id: 'delete-category-title',
                    className: 'brand-display mt-1 text-2xl text-[var(--ink)]'
                }, ['Hapus kategori ini?']),
                h('p', { className: 'mt-3 text-sm text-[var(--muted)]' }, [
                    'Kategori ',
                    h('span', { className: 'font-semibold text-[var(--ink)]' }, [name]),
                    ' akan dihapus permanen jika tidak dipakai transaksi.'
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

    return { openEdit: openEdit, openDelete: openDelete };
})();
</script>
