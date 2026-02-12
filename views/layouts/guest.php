<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Keuangan - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="/assets/js/ui/modal-core.js"></script>
    <style>
        :root {
            --bg: #faf5ec;
            --surface: #fffdf9;
            --ink: #1f2522;
            --muted: #626a62;
            --line: #dcd3c5;
            --accent: #1d6b4d;
            --shadow: 0 22px 56px -34px rgba(24, 31, 28, 0.5);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background:
                radial-gradient(1080px 520px at 95% -8%, #e6f1df 0%, rgba(230, 241, 223, 0) 72%),
                radial-gradient(960px 500px at -2% 100%, #f3e6cf 0%, rgba(243, 230, 207, 0) 68%),
                linear-gradient(180deg, #fbf7f0 0%, #f4eee1 100%);
            font-family: "Plus Jakarta Sans", "Segoe UI", sans-serif;
        }

        .brand-display {
            font-family: "Fraunces", Georgia, serif;
            letter-spacing: 0.01em;
        }

        .focus-ring:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        .glass-card {
            border: 1px solid rgba(220, 211, 197, 0.95);
            background: linear-gradient(140deg, rgba(255, 253, 249, 0.96), rgba(255, 249, 240, 0.92));
            box-shadow: var(--shadow);
        }
    </style>
</head>
<body>
    <main class="mx-auto grid min-h-screen max-w-6xl items-center gap-6 px-4 py-10 lg:grid-cols-[1.1fr_0.9fr] lg:px-6">
        <section class="hidden rounded-3xl border border-[var(--line)]/80 bg-[color:rgba(255,255,255,0.5)] p-10 text-[var(--ink)] shadow-[0_20px_50px_-38px_rgba(20,28,24,0.65)] lg:block">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--muted)]">Finance Automation</p>
            <h1 class="brand-display mt-4 text-5xl leading-[1.08]">Data pengeluaran harian, dikurasi jadi insight yang rapi.</h1>
            <p class="mt-6 max-w-xl text-sm leading-7 text-[var(--muted)]">Admin panel ini jadi cockpit untuk memantau performa pengeluaran dari chatbot WhatsApp, dengan fokus pada kejelasan angka, ritme data, dan eksekusi cepat.</p>
            <div class="mt-8 grid max-w-xl grid-cols-2 gap-4 text-sm">
                <div class="rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-4">
                    <p class="font-semibold">Laporan konsisten</p>
                    <p class="mt-1 text-xs text-[var(--muted)]">Ringkas dan mudah dipresentasikan.</p>
                </div>
                <div class="rounded-2xl border border-[var(--line)] bg-[var(--surface)] p-4">
                    <p class="font-semibold">Akses terbatas</p>
                    <p class="mt-1 text-xs text-[var(--muted)]">Hanya email allowlist yang bisa masuk.</p>
                </div>
            </div>
        </section>

        <section class="glass-card w-full max-w-md justify-self-center rounded-3xl p-7 sm:p-9">
            <?= $content ?>
        </section>
    </main>
</body>
</html>
