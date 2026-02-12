<?php
declare(strict_types=1);

use App\Core\View;

$successMessage = trim((string) ($success ?? ''));
$errorMessage = trim((string) ($error ?? ''));
?>
<?php if ($successMessage !== ''): ?>
    <div class="rounded-2xl border border-[var(--accent)]/20 bg-[var(--accent-soft)] px-4 py-3 text-sm text-[var(--accent)]">
        <?= View::escape($successMessage) ?>
    </div>
<?php endif; ?>

<?php if ($errorMessage !== ''): ?>
    <div class="rounded-2xl border border-[#cf8571]/45 bg-[#fff4ef] px-4 py-3 text-sm text-[#9d442f]">
        <?= View::escape($errorMessage) ?>
    </div>
<?php endif; ?>
