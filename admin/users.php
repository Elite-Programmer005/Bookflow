<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';

require_role('admin');

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'delete');

    if ($action === 'create') {
        $businessName = trim((string) ($_POST['business_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $slug = trim((string) ($_POST['slug'] ?? ''));

        if ($businessName === '' || $email === '' || $password === '') {
            flash('error', 'Business name, email, and password are required.');
            redirect_to('/admin/users');
        }

        if (strlen($password) < 6) {
            flash('error', 'Password must be at least 6 characters.');
            redirect_to('/admin/users');
        }

        if (fetch_user_by_email($email)) {
            flash('error', 'That email is already in use.');
            redirect_to('/admin/users');
        }

        $user = create_shopkeeper_account($email, $password, $businessName, $slug !== '' ? $slug : $businessName);
        ensure_default_working_hours((int) $user['id']);
        flash('success', 'Shopkeeper tenant created.');
        redirect_to('/admin/users');
    }

    $userId = (int) ($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        $stmt = db()->prepare('DELETE FROM users WHERE id = :id AND role = "shopkeeper"');
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
        flash('success', 'Shopkeeper removed.');
        redirect_to('/admin/users');
    }
}

$result = db()->query('SELECT * FROM users WHERE role = "shopkeeper" ORDER BY business_name ASC');
$rows = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $rows[] = $row;
}

$pageTitle = 'Shopkeepers';
require __DIR__ . '/../templates/header.php';
?>
<div class="rounded-[2rem] bg-white p-6 shadow-xl">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900">Shopkeepers</h1>
            <p class="text-sm text-slate-500">Delete or review tenant accounts.</p>
        </div>
        <a href="<?= e(app_url('/admin/dashboard')) ?>" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">Back to dashboard</a>
    </div>

    <form method="post" class="mt-6 rounded-[2rem] border border-slate-100 bg-slate-50 p-5 shadow-sm animate-rise">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="grid flex-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Business name</label>
                    <input name="business_name" type="text" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3" placeholder="New Salon">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                    <input name="email" type="email" required class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3" placeholder="owner@example.com">
                </div>
                <div class="field-with-toggle">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                    <div class="field-toggle-wrap">
                        <input id="admin_create_password" name="password" type="password" required minlength="6" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3" placeholder="At least 6 characters">
                        <button type="button" class="field-toggle-button" data-password-toggle="#admin_create_password">Show</button>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Slug</label>
                    <input name="slug" type="text" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3" placeholder="new-salon">
                </div>
            </div>
            <button class="rounded-2xl bg-slate-900 px-5 py-3 font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-slate-700 hover:shadow-lg">Add tenant</button>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="text-slate-500">
                <tr>
                    <th class="py-3 pr-4">Business</th>
                    <th class="py-3 pr-4">Email</th>
                    <th class="py-3 pr-4">Slug</th>
                    <th class="py-3 pr-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="py-4 pr-4 font-semibold text-slate-900"><?= e((string) $row['business_name']) ?></td>
                        <td class="py-4 pr-4 text-slate-600"><?= e($row['email']) ?></td>
                        <td class="py-4 pr-4 text-slate-600"><?= e((string) $row['slug']) ?></td>
                        <td class="py-4 pr-4">
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                                <button class="rounded-xl border border-rose-200 px-3 py-2 font-medium text-rose-700" data-confirm="Delete this shopkeeper and all related data?">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
require __DIR__ . '/../templates/footer.php';
