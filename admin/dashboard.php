<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';

require_role('admin');

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($action === 'toggle' && $userId > 0) {
        $stmt = db()->prepare('UPDATE users SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = :id AND role = "shopkeeper"');
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
        flash('success', 'Shopkeeper status updated.');
        redirect_to('/admin/dashboard');
    }

    if ($action === 'delete' && $userId > 0) {
        if ($userId === current_user_id()) {
            flash('error', 'You cannot delete your own admin account.');
            redirect_to('/admin/dashboard');
        }

        $stmt = db()->prepare('DELETE FROM users WHERE id = :id AND role = "shopkeeper"');
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();
        flash('success', 'Shopkeeper deleted.');
        redirect_to('/admin/dashboard');
    }
}

$shopkeepers = [];
$result = db()->query('SELECT * FROM users WHERE role = "shopkeeper" ORDER BY created_at DESC, id DESC');
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $shopkeepers[] = $row;
}

$bookings = fetch_all_bookings();
$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../templates/header.php';
?>
<div class="grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">
    <aside class="rounded-[2rem] bg-slate-900 p-6 text-white shadow-2xl shadow-slate-300/40 transition duration-200 hover:shadow-slate-400/40 lg:sticky lg:top-6 lg:self-start">
        <div class="text-xs uppercase tracking-[0.3em] text-slate-400">Admin</div>
        <h1 class="mt-3 text-3xl font-black">Dashboard</h1>
        <nav class="mt-8 space-y-2 text-sm font-medium">
            <a href="<?= e(app_url('/admin/dashboard')) ?>" class="block rounded-2xl bg-white/10 px-4 py-3 transition duration-200 hover:bg-white/15 hover:translate-x-1">Overview</a>
            <a href="<?= e(app_url('/admin/users')) ?>" class="block rounded-2xl px-4 py-3 transition duration-200 hover:bg-white/10 hover:translate-x-1">Shopkeepers</a>
            <a href="<?= e(app_url('/logout')) ?>" class="block rounded-2xl px-4 py-3 transition duration-200 hover:bg-white/10 hover:translate-x-1">Logout</a>
        </nav>
    </aside>

    <section class="space-y-8">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-3xl bg-white p-6 shadow-lg transition duration-200 hover:-translate-y-1 hover:shadow-2xl">
                <div class="text-sm text-slate-500">Shopkeepers</div>
                <div class="mt-2 text-3xl font-black text-slate-900"><?= count($shopkeepers) ?></div>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-lg transition duration-200 hover:-translate-y-1 hover:shadow-2xl">
                <div class="text-sm text-slate-500">Bookings</div>
                <div class="mt-2 text-3xl font-black text-slate-900"><?= count($bookings) ?></div>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-lg transition duration-200 hover:-translate-y-1 hover:shadow-2xl">
                <div class="text-sm text-slate-500">Active tenants</div>
                <div class="mt-2 text-3xl font-black text-slate-900"><?= count(array_filter($shopkeepers, fn($shopkeeper) => (int) $shopkeeper['is_active'] === 1)) ?></div>
            </div>
        </div>

        <div class="rounded-[2rem] bg-white p-6 shadow-xl transition duration-200 hover:shadow-2xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">All shopkeepers</h2>
                    <p class="text-sm text-slate-500">Manage visibility and remove demo accounts.</p>
                </div>
                <a href="<?= e(app_url('/register')) ?>" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-slate-700 hover:shadow-lg">Add shopkeeper</a>
            </div>
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-slate-500">
                        <tr>
                            <th class="py-3 pr-4">Business</th>
                            <th class="py-3 pr-4">Email</th>
                            <th class="py-3 pr-4">Slug</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($shopkeepers as $shopkeeper): ?>
                            <tr>
                                <td class="py-4 pr-4 font-semibold text-slate-900"><?= e((string) $shopkeeper['business_name']) ?></td>
                                <td class="py-4 pr-4 text-slate-600"><?= e($shopkeeper['email']) ?></td>
                                <td class="py-4 pr-4 text-slate-600"><?= e((string) $shopkeeper['slug']) ?></td>
                                <td class="py-4 pr-4">
                                    <span class="badge <?= (int) $shopkeeper['is_active'] === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' ?>"><?= (int) $shopkeeper['is_active'] === 1 ? 'Active' : 'Disabled' ?></span>
                                </td>
                                <td class="py-4 pr-4">
                                    <form method="post" class="flex flex-wrap gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int) $shopkeeper['id'] ?>">
                                        <button name="action" value="toggle" class="rounded-xl border border-slate-200 px-3 py-2 font-medium text-slate-700">Toggle</button>
                                        <button name="action" value="delete" data-confirm="Delete this shopkeeper?" class="rounded-xl border border-rose-200 px-3 py-2 font-medium text-rose-700">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-[2rem] bg-white p-6 shadow-xl">
            <h2 class="text-2xl font-bold text-slate-900">All bookings</h2>
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-slate-500">
                        <tr>
                            <th class="py-3 pr-4">Tenant</th>
                            <th class="py-3 pr-4">Customer</th>
                            <th class="py-3 pr-4">Service</th>
                            <th class="py-3 pr-4">Date</th>
                            <th class="py-3 pr-4">Time</th>
                            <th class="py-3 pr-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td class="py-4 pr-4 text-slate-600"><?= e((string) $booking['business_name']) ?></td>
                                <td class="py-4 pr-4 font-medium text-slate-900"><?= e($booking['customer_name']) ?></td>
                                <td class="py-4 pr-4 text-slate-600"><?= e($booking['service_name']) ?></td>
                                <td class="py-4 pr-4 text-slate-600"><?= e($booking['booking_date']) ?></td>
                                <td class="py-4 pr-4 text-slate-600"><?= e($booking['start_time'] . ' - ' . $booking['end_time']) ?></td>
                                <td class="py-4 pr-4"><span class="badge <?= e(status_badge_class((string) $booking['status'])) ?>"><?= e((string) $booking['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
<?php
require __DIR__ . '/../templates/footer.php';
