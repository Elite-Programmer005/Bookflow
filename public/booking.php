<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../auth.php';

$slug = (string) ($_GET['slug'] ?? '');

if ($slug === '') {
    redirect_to('/');
}

$shopkeeper = fetch_user_by_slug($slug);

if (!$shopkeeper) {
    http_response_code(404);
    require __DIR__ . '/../templates/header.php';
    ?>
    <div class="rounded-3xl border border-rose-200 bg-rose-50 p-8 text-rose-800">
        <h1 class="text-2xl font-bold">Booking page not found</h1>
        <p class="mt-2">The requested business slug is invalid or unavailable.</p>
    </div>
    <?php
    require __DIR__ . '/../templates/footer.php';
    return;
}

$services = fetch_services((int) $shopkeeper['id']);
$workingHours = fetch_working_hours((int) $shopkeeper['id']);
$selectedServiceId = (int) ($_GET['service_id'] ?? ($services[0]['id'] ?? 0));
$selectedDate = (string) ($_GET['date'] ?? date('Y-m-d'));
$availableSlots = [];
$isAdvanceBooking = false;
$selectedDayOfWeek = (int) date('N', strtotime($selectedDate)) - 1;
$selectedDayHours = $workingHours[$selectedDayOfWeek] ?? null;

if ($selectedServiceId > 0 && validate_booking_date($selectedDate)) {
    $availableSlots = generateTimeSlots((int) $shopkeeper['id'], $selectedServiceId, $selectedDate);
}

$selectedDateObject = DateTimeImmutable::createFromFormat('Y-m-d', $selectedDate);
if ($selectedDateObject instanceof DateTimeImmutable) {
    $isAdvanceBooking = $selectedDateObject > new DateTimeImmutable('today');
}

if (is_post()) {
    verify_csrf();
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $bookingDate = (string) ($_POST['booking_date'] ?? '');
    $startTime = (string) ($_POST['start_time'] ?? '');
    $customerName = trim((string) ($_POST['customer_name'] ?? ''));
    $customerEmail = trim((string) ($_POST['customer_email'] ?? ''));
    $customerPhone = trim((string) ($_POST['customer_phone'] ?? ''));
    $dummyPayment = (int) ($_POST['dummy_payment'] ?? 0) === 1;

    $serviceStmt = db()->prepare('SELECT * FROM services WHERE id = :id AND shopkeeper_id = :shopkeeper_id AND is_active = 1 LIMIT 1');
    $serviceStmt->bindValue(':id', $serviceId, SQLITE3_INTEGER);
    $serviceStmt->bindValue(':shopkeeper_id', (int) $shopkeeper['id'], SQLITE3_INTEGER);
    $service = $serviceStmt->execute()->fetchArray(SQLITE3_ASSOC);

    $bookingDateObject = DateTimeImmutable::createFromFormat('Y-m-d', $bookingDate);
    $isAdvanceBooking = $bookingDateObject instanceof DateTimeImmutable && $bookingDateObject > new DateTimeImmutable('today');

    if (!$service || !validate_booking_date($bookingDate) || $customerName === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || $startTime === '') {
        flash('error', 'Please complete the booking form and choose a valid slot.');
        redirect_to('/' . $shopkeeper['slug']);
    }

    if ($isAdvanceBooking && !$dummyPayment) {
        flash('error', 'Advance bookings require demo payment to confirm the slot.');
        redirect_to('/' . $shopkeeper['slug'] . '?service_id=' . $serviceId . '&date=' . urlencode($bookingDate));
    }

    $validSlots = generateTimeSlots((int) $shopkeeper['id'], $serviceId, $bookingDate);
    if (!in_array($startTime, $validSlots, true)) {
        flash('error', 'That slot is no longer available.');
        redirect_to('/' . $shopkeeper['slug'] . '?service_id=' . $serviceId . '&date=' . urlencode($bookingDate));
    }

    $endTime = booking_end_time($startTime, (int) $service['duration_minutes']);
    $status = ($dummyPayment || $isAdvanceBooking) ? 'confirmed' : 'pending';
    $paidDummy = $dummyPayment ? 1 : 0;

    $stmt = db()->prepare('INSERT INTO bookings (shopkeeper_id, service_id, customer_name, customer_email, customer_phone, booking_date, start_time, end_time, status, paid_dummy)
        VALUES (:shopkeeper_id, :service_id, :customer_name, :customer_email, :customer_phone, :booking_date, :start_time, :end_time, :status, :paid_dummy)');
    $stmt->bindValue(':shopkeeper_id', (int) $shopkeeper['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':service_id', $serviceId, SQLITE3_INTEGER);
    $stmt->bindValue(':customer_name', $customerName, SQLITE3_TEXT);
    $stmt->bindValue(':customer_email', $customerEmail, SQLITE3_TEXT);
    $stmt->bindValue(':customer_phone', $customerPhone, SQLITE3_TEXT);
    $stmt->bindValue(':booking_date', $bookingDate, SQLITE3_TEXT);
    $stmt->bindValue(':start_time', $startTime, SQLITE3_TEXT);
    $stmt->bindValue(':end_time', $endTime, SQLITE3_TEXT);
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->bindValue(':paid_dummy', $paidDummy, SQLITE3_INTEGER);
    $stmt->execute();

    $message = $dummyPayment ? 'Booking confirmed with demo payment.' : 'Booking saved as pending.';
    flash('success', $message);
    redirect_to('/' . $shopkeeper['slug'] . '?service_id=' . $serviceId . '&date=' . urlencode($bookingDate));
}

$pageTitle = business_label($shopkeeper);
require __DIR__ . '/../templates/header.php';
?>
<div class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr]">
    <section class="space-y-6">
        <div class="rounded-[2rem] bg-slate-900 p-8 text-white shadow-2xl shadow-slate-300/40 animate-rise">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-sky-200">Public booking</span>
                    <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl animate-soft-float"><?= e(business_label($shopkeeper)) ?></h1>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300">Choose a service, date, and time slot. Payment is simulated for demo use only.</p>
                </div>
                <div class="rounded-3xl bg-white/10 px-5 py-4 text-right">
                    <div class="text-xs uppercase tracking-[0.3em] text-slate-300">Slug</div>
                    <div class="text-lg font-semibold"><?= e($shopkeeper['slug']) ?></div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <?php foreach ($services as $service): ?>
                <a href="<?= e(app_url('/' . $shopkeeper['slug'] . '?service_id=' . (int) $service['id'] . '&date=' . urlencode($selectedDate))) ?>" class="rounded-3xl border <?= (int) $service['id'] === $selectedServiceId ? 'border-slate-900 bg-slate-900 text-white' : 'border-white bg-white' ?> p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg animate-rise">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold"><?= e($service['name']) ?></h2>
                            <p class="mt-1 text-sm opacity-75"><?= (int) $service['duration_minutes'] ?> minutes</p>
                        </div>
                        <div class="rounded-full px-3 py-1 text-sm font-semibold <?= (int) $service['id'] === $selectedServiceId ? 'bg-white/15' : 'bg-slate-100 text-slate-700' ?>">$<?= number_format((float) $service['price'], 2) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="get" class="rounded-[2rem] border border-white bg-white p-6 shadow-lg animate-rise">
            <input type="hidden" name="slug" value="<?= e($shopkeeper['slug']) ?>">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Service</label>
                    <select name="service_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <?php foreach ($services as $service): ?>
                            <option value="<?= (int) $service['id'] ?>" <?= (int) $service['id'] === $selectedServiceId ? 'selected' : '' ?>><?= e($service['name']) ?> - <?= (int) $service['duration_minutes'] ?> min</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Date</label>
                    <input name="date" type="date" value="<?= e($selectedDate) ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                </div>
                <div class="flex items-end">
                    <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white">Check availability</button>
                </div>
            </div>
        </form>

        <div class="rounded-[2rem] border border-white bg-white p-6 shadow-lg animate-rise">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Working hours</h2>
                    <p class="text-sm text-slate-500">Slots are generated from the shop's saved schedule.</p>
                </div>
                <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"><?= e(day_name($selectedDayOfWeek)) ?></div>
            </div>
            <div class="mt-4">
                <?php if ($selectedDayHours && (int) $selectedDayHours['is_closed'] !== 1): ?>
                    <div class="inline-flex rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        <?= e($selectedDayHours['start_time']) ?> - <?= e($selectedDayHours['end_time']) ?>
                    </div>
                <?php else: ?>
                    <div class="inline-flex rounded-2xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                        Closed on this day
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-[2rem] border border-white bg-white p-6 shadow-lg animate-rise">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Available slots</h2>
                    <p class="text-sm text-slate-500"><?= e($selectedDate) ?></p>
                </div>
                <div class="text-sm text-slate-500"><?= count($availableSlots) ?> open</div>
            </div>
            <div class="mt-5 flex flex-wrap gap-3">
                <?php if (!$availableSlots): ?>
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500">No slots available for the selected date.</div>
                <?php endif; ?>
                <?php foreach ($availableSlots as $slot): ?>
                    <button type="button" data-slot="<?= e($slot) ?>" class="booking-slot rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-100"><?= e($slot) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <aside class="space-y-6">
        <form id="bookingForm" method="post" class="rounded-[2rem] border border-white bg-white p-6 shadow-xl animate-rise">
            <?= csrf_field() ?>
            <input type="hidden" name="service_id" value="<?= (int) $selectedServiceId ?>">
            <input type="hidden" name="booking_date" value="<?= e($selectedDate) ?>">
            <input type="hidden" value="" data-booking-time-input>
            <input type="hidden" name="dummy_payment" value="0">
            <h2 class="text-2xl font-bold text-slate-900">Book now</h2>
            <p class="mt-1 text-sm text-slate-500">Choose a time slot, fill in your details, and pay dummy for advance bookings.</p>
            <div class="mt-5 space-y-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Selected time</label>
                    <select name="start_time" data-booking-time-select class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <option value="">Choose a slot</option>
                        <?php foreach ($availableSlots as $slot): ?>
                            <option value="<?= e($slot) ?>"><?= e($slot) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Your name</label>
                    <input name="customer_name" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                    <input name="customer_email" type="email" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Phone</label>
                    <input name="customer_phone" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                </div>
                <div class="rounded-2xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-800">
                    <?= $isAdvanceBooking ? 'Advance booking: demo payment is required to confirm this slot.' : 'Same-day booking can be saved as pending without demo payment.' ?>
                </div>
            </div>
            <div class="mt-6 grid gap-3">
                <button type="button" data-dummy-pay data-form-target="#bookingForm" class="rounded-2xl bg-emerald-600 px-4 py-3 font-semibold text-white transition hover:bg-emerald-500">Pay Dummy &amp; Book</button>
                <button type="submit" class="rounded-2xl border border-slate-200 px-4 py-3 font-semibold text-slate-700 transition hover:bg-slate-50">Save without dummy payment</button>
            </div>
        </form>
    </aside>
</div>
<?php
require __DIR__ . '/../templates/footer.php';
