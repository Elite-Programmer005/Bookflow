<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';

require_role('shopkeeper');
$shopkeeper = current_user();

$bookings = fetch_bookings_for_shopkeeper((int) $shopkeeper['id']);
$services = fetch_services((int) $shopkeeper['id'], false);
$activeCount = count(array_filter($services, fn($service) => (int) $service['is_active'] === 1));
$upcomingCount = count(array_filter($bookings, fn($booking) => strtotime($booking['booking_date'] . ' ' . $booking['start_time']) >= time() && in_array($booking['status'], ['pending', 'confirmed'], true)));

$pageTitle = 'Shopkeeper Dashboard';
require __DIR__ . '/../templates/header.php';
?>
<div class="grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">
    <aside class="rounded-[2rem] bg-slate-900 p-6 text-white shadow-2xl shadow-slate-300/40 transition duration-200 hover:shadow-slate-400/40 lg:sticky lg:top-6 lg:self-start">
        <div class="text-xs uppercase tracking-[0.3em] text-slate-400">Shopkeeper</div>
        <h1 class="mt-3 text-3xl font-black animate-soft-float"><?= e(business_label($shopkeeper)) ?></h1>
        <nav class="mt-8 space-y-2 text-sm font-medium">
            <a href="<?= e(app_url('/shopkeeper/dashboard')) ?>" class="block rounded-2xl bg-white/10 px-4 py-3 transition duration-200 hover:bg-white/15 hover:translate-x-1">Dashboard</a>
            <a href="<?= e(app_url('/shopkeeper/services')) ?>" class="block rounded-2xl px-4 py-3 transition duration-200 hover:bg-white/10 hover:translate-x-1">Services</a>
            <a href="<?= e(app_url('/shopkeeper/hours')) ?>" class="block rounded-2xl px-4 py-3 transition duration-200 hover:bg-white/10 hover:translate-x-1">Working hours</a>
            <a href="<?= e(app_url('/shopkeeper/bookings')) ?>" class="block rounded-2xl px-4 py-3 transition duration-200 hover:bg-white/10 hover:translate-x-1">Bookings</a>
            <a href="<?= e(app_url('/shopkeeper/settings')) ?>" class="block rounded-2xl px-4 py-3 transition duration-200 hover:bg-white/10 hover:translate-x-1">Settings</a>
        </nav>
    </aside>

    <section class="space-y-8">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-3xl bg-white p-6 shadow-lg transition duration-200 hover:-translate-y-1 hover:shadow-2xl animate-rise">
                <div class="text-sm text-slate-500">Active services</div>
                <div class="mt-2 text-3xl font-black text-slate-900"><?= $activeCount ?></div>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-lg transition duration-200 hover:-translate-y-1 hover:shadow-2xl animate-rise">
                <div class="text-sm text-slate-500">Total bookings</div>
                <div class="mt-2 text-3xl font-black text-slate-900"><?= count($bookings) ?></div>
            </div>
            <div class="rounded-3xl bg-white p-6 shadow-lg transition duration-200 hover:-translate-y-1 hover:shadow-2xl animate-rise">
                <div class="text-sm text-slate-500">Upcoming</div>
                <div class="mt-2 text-3xl font-black text-slate-900"><?= $upcomingCount ?></div>
            </div>
        </div>

        <div class="rounded-[2rem] bg-white p-6 shadow-xl transition duration-200 hover:shadow-2xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Upcoming bookings</h2>
                    <p class="text-sm text-slate-500">Latest confirmed and pending appointments.</p>
                </div>
                <a href="<?= e(app_url('/shopkeeper/bookings')) ?>" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md">Open bookings</a>
            </div>
            <div class="mt-6 space-y-4">
                <?php foreach (array_slice($bookings, 0, 8) as $booking): ?>
                    <div class="flex flex-col gap-3 rounded-3xl border border-slate-100 p-5 transition duration-200 hover:-translate-y-0.5 hover:border-slate-200 hover:shadow-md md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="font-semibold text-slate-900"><?= e($booking['customer_name']) ?></div>
                            <div class="text-sm text-slate-500"><?= e($booking['service_name']) ?> · <?= e($booking['booking_date']) ?> · <?= e($booking['start_time']) ?></div>
                        </div>
                        <span class="badge <?= e(status_badge_class((string) $booking['status'])) ?>"><?= e((string) $booking['status']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$bookings): ?>
                    <div class="rounded-3xl border border-dashed border-slate-300 p-6 text-slate-500">No bookings yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
<?php
require __DIR__ . '/../templates/footer.php';
