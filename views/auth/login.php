<?php
declare(strict_types=1);
?>
<div class="space-y-6">
    <div class="space-y-2">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--muted)]">Secure Admin Access</p>
        <h1 class="brand-display text-4xl leading-tight text-[var(--ink)]">Masuk ke Control Room</h1>
        <p class="text-sm leading-6 text-[var(--muted)]">Autentikasi menggunakan akun Google yang terdaftar di allowlist admin untuk menjaga akses tetap eksklusif.</p>
    </div>

    <?php require __DIR__ . '/../partials/flash.php'; ?>

    <a
        href="/admin/auth/google"
        class="focus-ring group inline-flex w-full items-center justify-center gap-3 rounded-2xl border border-[var(--line)] bg-[var(--surface)] px-4 py-3 text-sm font-semibold text-[var(--ink)] transition hover:-translate-y-0.5 hover:border-[var(--accent)]/40 hover:bg-[#f7f6f2]"
    >
        <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#EA4335" d="M12 10.2v3.9h5.4c-.2 1.3-1.5 3.9-5.4 3.9-3.2 0-5.9-2.7-5.9-6s2.7-6 5.9-6c1.8 0 3 .8 3.7 1.4l2.5-2.4C16.8 3.7 14.6 3 12 3 7 3 3 7 3 12s4 9 9 9c5.2 0 8.7-3.6 8.7-8.8 0-.6-.1-1.1-.2-1.6H12z"/>
            <path fill="#34A853" d="M3 12c0 1.8.6 3.4 1.7 4.8l2.8-2.2c-.7-.6-1.2-1.6-1.2-2.6s.4-2 1.2-2.6L4.7 7.2C3.6 8.6 3 10.2 3 12z"/>
            <path fill="#4A90E2" d="M12 21c2.4 0 4.5-.8 6-2.3l-2.9-2.2c-.8.6-1.9 1-3.1 1-2.4 0-4.4-1.6-5.1-3.8l-3 2.3C5.4 19 8.4 21 12 21z"/>
            <path fill="#FBBC05" d="M6.9 13.7c-.2-.6-.3-1.1-.3-1.7s.1-1.1.3-1.7L4 8C3.4 9.2 3 10.6 3 12s.4 2.8 1 4l2.9-2.3z"/>
        </svg>
        <span class="tracking-[0.02em]">Masuk dengan Google</span>
    </a>

    <div class="rounded-2xl border border-dashed border-[var(--line)] px-4 py-3 text-xs leading-6 text-[var(--muted)]">
        Jika akses ditolak, pastikan email kamu sudah terdaftar di variabel <code class="rounded bg-[#f2ece2] px-1 py-0.5 text-[var(--ink)]">ADMIN_ALLOWED_EMAILS</code>.
    </div>
</div>
