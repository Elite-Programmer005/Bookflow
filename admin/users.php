<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';

require_role('admin');

if (is_post()) {
    verify_csrf();
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
        <a href="<?= e(app_url('/admin/dashboard.php')) ?>" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">Back to dashboard</a>
    </div>
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
