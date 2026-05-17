<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';

require_role('shopkeeper');
$shopkeeper = current_user();
$shopkeeperId = (int) $shopkeeper['id'];

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'create');
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $duration = max(15, (int) ($_POST['duration_minutes'] ?? 30));
    $price = (float) ($_POST['price'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($action === 'delete' && $serviceId > 0) {
        $stmt = db()->prepare('DELETE FROM services WHERE id = :id AND shopkeeper_id = :shopkeeper_id');
        $stmt->bindValue(':id', $serviceId, SQLITE3_INTEGER);
        $stmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
        $stmt->execute();
        flash('success', 'Service deleted.');
        redirect_to('/shopkeeper/services');
    }

    if ($name === '') {
        flash('error', 'Service name is required.');
        redirect_to('/shopkeeper/services');
    }

    if ($action === 'update' && $serviceId > 0) {
        $stmt = db()->prepare('UPDATE services SET name = :name, duration_minutes = :duration_minutes, price = :price, is_active = :is_active WHERE id = :id AND shopkeeper_id = :shopkeeper_id');
        $stmt->bindValue(':id', $serviceId, SQLITE3_INTEGER);
    } else {
        $stmt = db()->prepare('INSERT INTO services (shopkeeper_id, name, duration_minutes, price, is_active) VALUES (:shopkeeper_id, :name, :duration_minutes, :price, :is_active)');
        $stmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
    }

    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':duration_minutes', $duration, SQLITE3_INTEGER);
    $stmt->bindValue(':price', $price, SQLITE3_FLOAT);
    $stmt->bindValue(':is_active', $isActive, SQLITE3_INTEGER);
    if ($action === 'update' && $serviceId > 0) {
        $stmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
    }
    $stmt->execute();

    flash('success', $action === 'update' ? 'Service updated.' : 'Service created.');
    redirect_to('/shopkeeper/services');
}

$result = db()->prepare('SELECT * FROM services WHERE shopkeeper_id = :shopkeeper_id ORDER BY created_at DESC, id DESC');
$result->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
$query = $result->execute();
$services = [];
while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
    $services[] = $row;
}

$editing = null;
if (!empty($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($services as $service) {
        if ((int) $service['id'] === $editId) {
            $editing = $service;
            break;
        }
    }
}

$pageTitle = 'Services';
require __DIR__ . '/../templates/header.php';
?>
<div class="grid gap-8 lg:grid-cols-[380px_1fr]">
    <section class="rounded-[2rem] bg-white p-6 shadow-xl">
        <h1 class="text-3xl font-black text-slate-900"><?= $editing ? 'Edit service' : 'Add service' ?></h1>
        <form method="post" class="mt-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
            <input type="hidden" name="service_id" value="<?= $editing ? (int) $editing['id'] : 0 ?>">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Name</label>
                <input name="name" value="<?= e($editing['name'] ?? '') ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Duration (minutes)</label>
                <input name="duration_minutes" type="number" min="15" step="5" value="<?= e((string) ($editing['duration_minutes'] ?? 30)) ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Price</label>
                <input name="price" type="number" min="0" step="0.01" value="<?= e((string) ($editing['price'] ?? 0)) ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
            </div>
            <label class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                <input name="is_active" type="checkbox" <?= !isset($editing) || (int) ($editing['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                Active service
            </label>
            <div class="flex gap-3">
                <button class="rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white"><?= $editing ? 'Update' : 'Create' ?></button>
                <?php if ($editing): ?>
                    <a href="<?= e(app_url('/shopkeeper/services')) ?>" class="rounded-2xl border border-slate-200 px-4 py-3 font-semibold text-slate-700">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="rounded-[2rem] bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Your services</h2>
                <p class="text-sm text-slate-500">Manage the treatments your customers can book.</p>
            </div>
            <a href="<?= e(app_url('/shopkeeper/dashboard')) ?>" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">Dashboard</a>
        </div>
        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-slate-500">
                    <tr>
                        <th class="py-3 pr-4">Name</th>
                        <th class="py-3 pr-4">Duration</th>
                        <th class="py-3 pr-4">Price</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 pr-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td class="py-4 pr-4 font-semibold text-slate-900"><?= e($service['name']) ?></td>
                            <td class="py-4 pr-4 text-slate-600"><?= (int) $service['duration_minutes'] ?> min</td>
                            <td class="py-4 pr-4 text-slate-600">$<?= number_format((float) $service['price'], 2) ?></td>
                            <td class="py-4 pr-4"><span class="badge <?= (int) $service['is_active'] === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' ?>"><?= (int) $service['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                            <td class="py-4 pr-4">
                                <div class="flex flex-wrap gap-2">
                                    <a href="<?= e(app_url('/shopkeeper/services?edit=' . (int) $service['id'])) ?>" class="rounded-xl border border-slate-200 px-3 py-2 font-medium text-slate-700">Edit</a>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                                        <button data-confirm="Delete this service?" class="rounded-xl border border-rose-200 px-3 py-2 font-medium text-rose-700">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php
require __DIR__ . '/../templates/footer.php';
