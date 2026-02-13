<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;

$admin = Auth::user();
$adminName = trim((string) ($admin['name'] ?? 'Admin'));
$adminEmail = trim((string) ($admin['email'] ?? '-'));
$adminAvatar = trim((string) ($admin['avatar'] ?? ''));
$avatarInitial = strtoupper(substr($adminName !== '' ? $adminName : 'A', 0, 1));
$requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH);
$navItems = [
    ['label' => 'Dashboard', 'href' => '/admin'],
    ['label' => 'Kategori', 'href' => '/admin/categories'],
    ['label' => 'Pengeluaran', 'href' => '/admin/expenses'],
];
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Keuangan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="/assets/js/ui/modal-core.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <style>
        :root {
            --bg: #f4f1eb;
            --surface: #fffdf8;
            --surface-strong: #fff9ef;
            --ink: #1f2522;
            --muted: #5f685f;
            --line: #d8d1c4;
            --accent: #1c6b4a;
            --accent-soft: #d9efe4;
            --warn: #b5513a;
            --shadow: 0 18px 45px -28px rgba(29, 35, 31, 0.45);
        }

        * {
            box-sizing: border-box;
        }

        [x-cloak] {
            display: none !important;
        }

        body {
            margin: 0;
            color: var(--ink);
            background:
                radial-gradient(1200px 500px at 82% -6%, #e4f1de 0%, rgba(228, 241, 222, 0) 72%),
                radial-gradient(900px 460px at 0% 24%, #f1e7d4 0%, rgba(241, 231, 212, 0) 68%),
                repeating-linear-gradient(45deg, rgba(31, 37, 34, 0.022) 0 1px, transparent 1px 16px),
                var(--bg);
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
        }

        .brand-display {
            font-family: "Fraunces", Georgia, serif;
            letter-spacing: 0.01em;
        }

        .panel {
            background: linear-gradient(160deg, rgba(255, 253, 248, 0.96), rgba(255, 249, 239, 0.92));
            border: 1px solid rgba(216, 209, 196, 0.95);
            box-shadow: var(--shadow);
        }

        .fade-rise {
            animation: fadeRise 500ms ease both;
        }

        @keyframes fadeRise {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .focus-ring:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        .choices {
            margin-bottom: 0;
        }

        .choices__inner {
            min-height: 42px;
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            background: var(--surface);
            padding: 4px 10px;
            color: var(--ink);
            font-size: 0.875rem;
        }

        .is-focused .choices__inner,
        .is-open .choices__inner {
            border-color: var(--accent);
        }

        .choices__list--dropdown,
        .choices__list[aria-expanded] {
            z-index: 12000;
            border-color: var(--line);
            border-radius: 0.85rem;
            background: var(--surface);
            box-shadow: 0 12px 30px -20px rgba(29, 35, 31, 0.4);
        }

        .choices__list--dropdown.is-fixed-pos {
            position: fixed;
        }

        .choices__item--choice.is-highlighted {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .choices[data-type*="select-one"]::after {
            border-color: var(--muted) transparent transparent;
            right: 12px;
        }

        .choices[data-type*="select-one"].is-open::after {
            border-color: transparent transparent var(--muted);
        }
    </style>
</head>

<body class="min-h-screen" x-data="{ openMenu: false }">
    <header class="sticky top-0 z-40 border-b border-[var(--line)]/70 bg-[color:rgba(255,253,248,0.84)] backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-3 py-2.5 sm:gap-4 sm:px-4 sm:py-3">
            <a href="/admin" class="focus-ring inline-flex items-baseline gap-2 rounded-lg px-1 py-1 text-[var(--ink)] transition hover:text-[var(--accent)]">
                <span class="brand-display text-xl font-bold leading-none sm:text-2xl">Admin Keuangan</span>
            </a>

            <nav class="hidden items-center gap-2 md:flex md:flex-1 md:justify-center">
                <?php foreach ($navItems as $item): ?>
                    <?php $isActive = $requestPath === $item['href']; ?>
                    <a
                        href="<?= View::escape((string) $item['href']) ?>"
                        class="focus-ring rounded-xl border px-3 py-2 text-sm font-semibold transition <?= $isActive ? 'border-[var(--accent)]/35 bg-[var(--accent-soft)] text-[var(--accent)]' : 'border-[var(--line)] bg-[var(--surface)] text-[var(--ink)] hover:border-[var(--accent)]/30 hover:text-[var(--accent)]' ?>">
                        <?= View::escape((string) $item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <button
                class="focus-ring h-11 rounded-xl border border-[var(--line)] bg-[var(--surface)] px-4 text-sm font-semibold text-[var(--ink)] md:hidden"
                @click="openMenu = !openMenu">
                Menu
            </button>

            <div class="hidden items-center gap-3 md:flex">
                <div class="panel flex items-center gap-3 rounded-2xl px-3 py-2">
                    <?php if ($adminAvatar !== ''): ?>
                        <img src="<?= View::escape($adminAvatar) ?>" alt="Avatar admin" referrerpolicy="no-referrer" class="h-9 w-9 rounded-full border border-[var(--line)] object-cover">
                    <?php else: ?>
                        <span class="flex h-9 w-9 items-center justify-center rounded-full border border-[var(--line)] bg-[var(--surface-strong)] text-sm font-bold text-[var(--accent)]">
                            <?= View::escape($avatarInitial) ?>
                        </span>
                    <?php endif; ?>
                    <div class="leading-tight">
                        <p class="text-sm font-semibold text-[var(--ink)]"><?= View::escape($adminName) ?></p>
                        <p class="max-w-[180px] truncate text-xs text-[var(--muted)]"><?= View::escape($adminEmail) ?></p>
                    </div>
                </div>
                <form method="POST" action="/admin/logout">
                    <input type="hidden" name="_token" value="<?= View::escape(Csrf::token()) ?>">
                    <button type="submit" class="focus-ring rounded-xl border border-[var(--warn)]/35 bg-[var(--surface)] px-3 py-2 text-sm font-semibold text-[var(--warn)] transition hover:-translate-y-0.5 hover:border-[var(--warn)]/70 hover:bg-[#fff3ef]">Keluar</button>
                </form>
            </div>
        </div>

        <div
            class="border-t border-[var(--line)]/80 bg-[var(--surface)] px-3 py-3 sm:px-4 md:hidden"
            x-show="openMenu"
            x-transition.opacity.duration.250ms
            x-cloak>
            <div class="mb-3 grid gap-2">
                <?php foreach ($navItems as $item): ?>
                    <?php $isActive = $requestPath === $item['href']; ?>
                    <a
                        href="<?= View::escape((string) $item['href']) ?>"
                        class="focus-ring rounded-xl border px-3 py-2.5 text-sm font-semibold transition <?= $isActive ? 'border-[var(--accent)]/35 bg-[var(--accent-soft)] text-[var(--accent)]' : 'border-[var(--line)] bg-[var(--surface)] text-[var(--ink)]' ?>">
                        <?= View::escape((string) $item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="panel flex flex-col items-stretch gap-3 rounded-2xl px-3 py-3">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-[var(--ink)]"><?= View::escape($adminName) ?></p>
                    <p class="truncate text-xs text-[var(--muted)]"><?= View::escape($adminEmail) ?></p>
                </div>
                <form method="POST" action="/admin/logout">
                    <input type="hidden" name="_token" value="<?= View::escape(Csrf::token()) ?>">
                    <button type="submit" class="focus-ring w-full rounded-xl border border-[var(--warn)]/35 bg-[var(--surface)] px-3 py-2 text-sm font-semibold text-[var(--warn)]">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-3 py-5 fade-rise sm:px-4 sm:py-7">
        <?= $content ?>
    </main>
</body>

</html>