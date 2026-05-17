<?php
$pageTitle = $pageTitle ?? 'Welcome';
$currentUser = current_user();
$loginUrl = app_url('/login');
$registerUrl = app_url('/register');
$dashboardUrl = $currentUser
    ? ($currentUser['role'] === 'admin' ? app_url('/admin/dashboard') : app_url('/shopkeeper/dashboard'))
    : '';
$logoutUrl = app_url('/logout');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(render_page_title($pageTitle)) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <main class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.25),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(16,185,129,0.18),_transparent_28%),linear-gradient(180deg,_#0f172a_0%,_#111827_100%)]">
        <div class="mx-auto flex min-h-screen max-w-6xl items-center px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                <section class="space-y-6">
                    <div class="inline-flex items-center gap-3 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm text-slate-200 backdrop-blur">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        Multi-tenant appointment booking
                    </div>
                    <h1 class="max-w-2xl text-5xl font-black tracking-tight text-white sm:text-6xl">
                        Welcome to BookFlow
                    </h1>
                    <p class="max-w-2xl text-lg leading-8 text-slate-300">
                        BookFlow helps admins, shopkeepers, and customers manage appointments in one clean PHP + SQLite platform.
                        Each shopkeeper gets their own slug, services, working hours, and bookings.
                    </p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <?php if ($currentUser): ?>
                            <a href="<?= e($dashboardUrl) ?>" class="rounded-2xl bg-white px-5 py-3 font-semibold text-slate-900 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-200 hover:shadow-xl">
                                Dashboard
                            </a>
                            <a href="<?= e($logoutUrl) ?>" class="rounded-2xl border border-white/20 bg-white/5 px-5 py-3 font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-white/10 hover:shadow-xl">
                                Logout
                            </a>
                        <?php else: ?>
                            <a href="<?= e($loginUrl) ?>" class="rounded-2xl bg-white px-5 py-3 font-semibold text-slate-900 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-200 hover:shadow-xl">
                                Login
                            </a>
                            <a href="<?= e($registerUrl) ?>" class="rounded-2xl border border-white/20 bg-white/5 px-5 py-3 font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-white/10 hover:shadow-xl">
                                Shopkeeper Register
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="grid gap-4 pt-4 sm:grid-cols-3">
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur transition duration-200 hover:-translate-y-1 hover:border-white/20 hover:bg-white/10">
                            <div class="text-sm uppercase tracking-[0.25em] text-slate-400">Admins</div>
                            <div class="mt-2 text-lg font-semibold text-white">Manage tenants and bookings</div>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur transition duration-200 hover:-translate-y-1 hover:border-white/20 hover:bg-white/10">
                            <div class="text-sm uppercase tracking-[0.25em] text-slate-400">Shopkeepers</div>
                            <div class="mt-2 text-lg font-semibold text-white">Set services and hours</div>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur transition duration-200 hover:-translate-y-1 hover:border-white/20 hover:bg-white/10">
                            <div class="text-sm uppercase tracking-[0.25em] text-slate-400">Customers</div>
                            <div class="mt-2 text-lg font-semibold text-white">Book by public slug</div>
                        </div>
                    </div>
                </section>
                <aside class="rounded-[2rem] border border-white/10 bg-slate-900/80 p-8 shadow-2xl shadow-black/20 backdrop-blur transition duration-200 hover:-translate-y-1 hover:shadow-black/30">
                    <h2 class="text-2xl font-bold text-white">How it works</h2>
                    <div class="mt-6 space-y-4 text-sm leading-7 text-slate-300">
                        <p>Use <span class="font-semibold text-white">/login</span> for admin and shopkeeper access.</p>
                        <p>Each shopkeeper has a unique slug like <span class="font-semibold text-white">/salon-ahmed</span>.</p>
                        <p>Customers open the public slug page, choose a service, and book without logging in.</p>
                        <p>Demo payment is built in for testing and presentations.</p>
                    </div>
                    <div class="mt-8 rounded-3xl bg-white/5 p-5 transition duration-200 hover:bg-white/10">
                        <div class="text-xs uppercase tracking-[0.3em] text-slate-400">Demo account</div>
                        <div class="mt-2 text-lg font-semibold text-white">admin@bookflow.com</div>
                        <div class="text-sm text-slate-300">Password: admin123</div>
                    </div>
                </aside>
            </div>
        </div>
    </main>
</body>
</html>
