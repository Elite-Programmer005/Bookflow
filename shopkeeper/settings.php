<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';

require_role('shopkeeper');
$shopkeeper = current_user();
$shopkeeperId = (int) $shopkeeper['id'];

if (is_post()) {
    verify_csrf();
    $businessName = trim((string) ($_POST['business_name'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $dummyEnabled = isset($_POST['dummy_payment_enabled']) ? 1 : 0;

    if ($businessName === '' || $email === '') {
        flash('error', 'Business name and email are required.');
        redirect_to('/shopkeeper/settings');
    }

    $existing = fetch_user_by_email($email);
    if ($existing && (int) $existing['id'] !== $shopkeeperId) {
        flash('error', 'That email is already in use.');
        redirect_to('/shopkeeper/settings');
    }

    $requestedSlug = $slug !== '' ? slugify($slug) : slugify($businessName);
    $slugValue = $requestedSlug;
    $checkStmt = db()->prepare('SELECT id FROM users WHERE slug = :slug AND id != :id LIMIT 1');
    $checkStmt->bindValue(':slug', $requestedSlug, SQLITE3_TEXT);
    $checkStmt->bindValue(':id', $shopkeeperId, SQLITE3_INTEGER);
    $slugConflict = $checkStmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($slugConflict) {
        $slugValue = unique_slug(db(), $requestedSlug);
    }

    if ($password !== '' && strlen($password) < 6) {
        flash('error', 'Password must be at least 6 characters.');
        redirect_to('/shopkeeper/settings');
    }

    $sql = 'UPDATE users SET business_name = :business_name, slug = :slug, email = :email, dummy_payment_enabled = :dummy_payment_enabled';
    if ($password !== '') {
        $sql .= ', password = :password';
    }
    $sql .= ' WHERE id = :id AND role = "shopkeeper"';

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':business_name', $businessName, SQLITE3_TEXT);
    $stmt->bindValue(':slug', $slugValue, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':dummy_payment_enabled', $dummyEnabled, SQLITE3_INTEGER);
    if ($password !== '') {
        $stmt->bindValue(':password', password_hash($password, PASSWORD_BCRYPT), SQLITE3_TEXT);
    }
    $stmt->bindValue(':id', $shopkeeperId, SQLITE3_INTEGER);
    $stmt->execute();

    $_SESSION['user_id'] = $shopkeeperId;
    flash('success', 'Settings updated.');
    redirect_to('/shopkeeper/settings');
}

$shopkeeper = current_user();
$pageTitle = 'Settings';
require __DIR__ . '/../templates/header.php';
?>
<div class="rounded-[2rem] bg-white p-6 shadow-xl">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900">Business settings</h1>
            <p class="text-sm text-slate-500">Update profile, slug, and demo payment preference.</p>
        </div>
        <a href="<?= e(app_url('/shopkeeper/dashboard')) ?>" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">Dashboard</a>
    </div>

    <form method="post" class="mt-6 grid gap-4 md:grid-cols-2">
        <?= csrf_field() ?>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Business name</label>
            <input name="business_name" value="<?= e((string) $shopkeeper['business_name']) ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Slug</label>
            <input name="slug" value="<?= e((string) $shopkeeper['slug']) ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
            <input name="email" type="email" value="<?= e($shopkeeper['email']) ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
        </div>
        <div class="field-with-toggle">
            <label class="mb-2 block text-sm font-semibold text-slate-700">New password</label>
            <div class="field-toggle-wrap">
                <input id="shopkeeper_password" name="password" type="password" minlength="6" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" placeholder="Leave blank to keep current password">
                <button type="button" class="field-toggle-button" data-password-toggle="#shopkeeper_password">Show</button>
            </div>
        </div>
        <div class="md:col-span-2">
            <label class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                <input type="checkbox" name="dummy_payment_enabled" <?= (int) ($shopkeeper['dummy_payment_enabled'] ?? 1) === 1 ? 'checked' : '' ?>>
                Dummy payment enabled for public booking page
            </label>
        </div>
        <div class="md:col-span-2">
            <button class="rounded-2xl bg-slate-900 px-5 py-3 font-semibold text-white">Save settings</button>
        </div>
    </form>
</div>
<?php
require __DIR__ . '/../templates/footer.php';
