<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';

require_role('shopkeeper');
$shopkeeper = current_user();
$shopkeeperId = (int) $shopkeeper['id'];

if (is_post()) {
    verify_csrf();
    $stmt = db()->prepare('INSERT INTO working_hours (shopkeeper_id, day_of_week, start_time, end_time, is_closed)
        VALUES (:shopkeeper_id, :day_of_week, :start_time, :end_time, :is_closed)
        ON CONFLICT(shopkeeper_id, day_of_week) DO UPDATE SET
            start_time = excluded.start_time,
            end_time = excluded.end_time,
            is_closed = excluded.is_closed');

    for ($day = 0; $day <= 6; $day++) {
        $closed = isset($_POST['closed'][$day]) ? 1 : 0;
        $start = trim((string) ($_POST['start'][$day] ?? '09:00'));
        $end = trim((string) ($_POST['end'][$day] ?? '17:00'));
        if ($closed) {
            $start = '00:00';
            $end = '00:00';
        }

        $stmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
        $stmt->bindValue(':day_of_week', $day, SQLITE3_INTEGER);
        $stmt->bindValue(':start_time', $start, SQLITE3_TEXT);
        $stmt->bindValue(':end_time', $end, SQLITE3_TEXT);
        $stmt->bindValue(':is_closed', $closed, SQLITE3_INTEGER);
        $stmt->execute();
    }

    flash('success', 'Working hours updated.');
    redirect_to('/shopkeeper/hours');
}

$hours = fetch_working_hours($shopkeeperId);
$pageTitle = 'Working Hours';
require __DIR__ . '/../templates/header.php';
?>
<div class="rounded-[2rem] bg-white p-6 shadow-xl">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900">Working hours</h1>
            <p class="text-sm text-slate-500">Set the weekly schedule for this tenant.</p>
        </div>
        <a href="<?= e(app_url('/shopkeeper/dashboard')) ?>" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700">Dashboard</a>
    </div>

    <form method="post" class="mt-6 space-y-4">
        <?= csrf_field() ?>
        <?php for ($day = 0; $day <= 6; $day++): ?>
            <?php $row = $hours[$day] ?? ['start_time' => '09:00', 'end_time' => '17:00', 'is_closed' => $day === 6 ? 1 : 0]; ?>
            <div class="grid gap-4 rounded-3xl border border-slate-100 p-4 md:grid-cols-[140px_1fr_1fr_120px] md:items-center">
                <div class="font-semibold text-slate-900"><?= e(day_name($day)) ?></div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Start</label>
                    <input name="start[<?= $day ?>]" type="time" value="<?= e($row['start_time']) ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">End</label>
                    <input name="end[<?= $day ?>]" type="time" value="<?= e($row['end_time']) ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                </div>
                <label class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="closed[<?= $day ?>]" <?= (int) $row['is_closed'] === 1 ? 'checked' : '' ?>>
                    Closed
                </label>
            </div>
        <?php endfor; ?>
        <div class="pt-4">
            <button class="rounded-2xl bg-slate-900 px-5 py-3 font-semibold text-white">Save schedule</button>
        </div>
    </form>
</div>
<?php
require __DIR__ . '/../templates/footer.php';
