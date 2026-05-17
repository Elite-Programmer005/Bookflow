<?php
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}
$currentUser = current_user();
$base = app_base_path();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(render_page_title($pageTitle)) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="<?= e(app_url('/assets/style.css')) ?>">
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.12),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.10),_transparent_24%),linear-gradient(180deg,_#f8fafc_0%,_#f1f5f9_100%)]">
    <header class="border-b border-white/60 bg-white/80 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="<?= e(app_url('/')) ?>" class="flex items-center gap-3 font-semibold tracking-tight text-slate-900">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-slate-900 text-white shadow-lg shadow-slate-300/40">BF</span>
                <span>
                    <span class="block text-lg leading-none"><?= e(APP_NAME) ?></span>
                    <span class="text-xs font-medium uppercase tracking-[0.3em] text-slate-500">Appointments</span>
                </span>
            </a>
            <nav class="flex items-center gap-3 text-sm font-medium text-slate-600">
                <?php if ($currentUser): ?>
                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a class="rounded-full px-4 py-2 hover:bg-slate-100" href="<?= e(app_url('/admin/dashboard.php')) ?>">Admin</a>
                        <a class="rounded-full px-4 py-2 hover:bg-slate-100" href="<?= e(app_url('/admin/users')) ?>">Users</a>
                    <?php else: ?>
                        <a class="rounded-full px-4 py-2 hover:bg-slate-100" href="<?= e(app_url('/shopkeeper/dashboard.php')) ?>">Dashboard</a>
                        <a class="rounded-full px-4 py-2 hover:bg-slate-100" href="<?= e(app_url('/shopkeeper/services')) ?>">Services</a>
                        <a class="rounded-full px-4 py-2 hover:bg-slate-100" href="<?= e(app_url('/shopkeeper/bookings')) ?>">Bookings</a>
                    <?php endif; ?>
                    <span class="hidden rounded-full bg-slate-100 px-4 py-2 text-slate-700 sm:inline-flex"><?= e($currentUser['email']) ?></span>
                    <a class="rounded-full bg-slate-900 px-4 py-2 text-white hover:bg-slate-700" href="<?= e(app_url('/logout')) ?>">Logout</a>
                <?php else: ?>
                    <a class="rounded-full px-4 py-2 hover:bg-slate-100" href="<?= e(app_url('/login')) ?>">Login</a>
                    <a class="rounded-full bg-slate-900 px-4 py-2 text-white hover:bg-slate-700" href="<?= e(app_url('/register')) ?>">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <?php if ($msg = flash('success')): ?>
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash('error')): ?>
            <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= e($msg) ?></div>
        <?php endif; ?>
