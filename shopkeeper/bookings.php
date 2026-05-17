<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';

require_role('shopkeeper');
$shopkeeper = current_user();
$shopkeeperId = (int) $shopkeeper['id'];

if (is_post()) {
    verify_csrf();
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    $allowed = ['confirmed', 'cancelled', 'completed'];

    if ($bookingId > 0 && in_array($status, $allowed, true)) {
        $stmt = db()->prepare('UPDATE bookings SET status = :status WHERE id = :id AND shopkeeper_id = :shopkeeper_id');
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':id', $bookingId, SQLITE3_INTEGER);
        $stmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
        $stmt->execute();
        flash('success', 'Booking updated.');
    }
    redirect_to('/shopkeeper/bookings');
}

$bookings = fetch_bookings_for_shopkeeper($shopkeeperId);
$pageTitle = 'Bookings';
require __DIR__ . '/../templates/header.php';
?>
<div class="rounded-[2rem] bg-white p-6 shadow-xl">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900">Bookings</h1>
            <p class="text-sm text-slate-500">Confirm, cancel, or mark appointments as completed.</p>
        </div>
        <a href="<?= e(app_url('/shopkeeper/dashboard')) ?>" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">Dashboard</a>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="text-slate-500">
                <tr>
                    <th class="py-3 pr-4">Customer</th>
                    <th class="py-3 pr-4">Service</th>
                    <th class="py-3 pr-4">Date</th>
                    <th class="py-3 pr-4">Time</th>
                    <th class="py-3 pr-4">Payment</th>
                    <th class="py-3 pr-4">Status</th>
                    <th class="py-3 pr-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td class="py-4 pr-4 font-semibold text-slate-900"><?= e($booking['customer_name']) ?></td>
                        <td class="py-4 pr-4 text-slate-600"><?= e($booking['service_name']) ?></td>
                        <td class="py-4 pr-4 text-slate-600"><?= e($booking['booking_date']) ?></td>
                        <td class="py-4 pr-4 text-slate-600"><?= e($booking['start_time'] . ' - ' . $booking['end_time']) ?></td>
                        <td class="py-4 pr-4 text-slate-600"><?= (int) $booking['paid_dummy'] === 1 ? 'Paid demo' : 'Unpaid' ?></td>
                        <td class="py-4 pr-4"><span class="badge <?= e(status_badge_class((string) $booking['status'])) ?>"><?= e((string) $booking['status']) ?></span></td>
                        <td class="py-4 pr-4">
                            <form method="post" class="flex flex-wrap gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                <button name="status" value="confirmed" class="rounded-xl border border-slate-200 px-3 py-2 font-medium text-slate-700">Confirm</button>
                                <button name="status" value="completed" class="rounded-xl border border-sky-200 px-3 py-2 font-medium text-sky-700">Complete</button>
                                <button name="status" value="cancelled" data-confirm="Cancel this booking?" class="rounded-xl border border-rose-200 px-3 py-2 font-medium text-rose-700">Cancel</button>
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
