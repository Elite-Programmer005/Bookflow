<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$route = current_path();

if ($route === '/' || $route === '') {
    if (is_logged_in()) {
        safe_redirect_after_login();
    }
    redirect_to('/login');
}

if ($route === '/logout') {
    logout_user();
    flash('success', 'You have been logged out.');
    redirect_to('/login');
}

if ($route === '/login') {
    if (is_logged_in()) {
        safe_redirect_after_login();
    }

    if (is_post()) {
        verify_csrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            flash('error', 'Email and password are required.');
            redirect_to('/login');
        }

        if (!authenticate_user($email, $password)) {
            flash('error', 'Invalid credentials or inactive account.');
            redirect_to('/login');
        }

        safe_redirect_after_login();
    }

    $pageTitle = 'Login';
    require __DIR__ . '/templates/header.php';
    ?>
    <div class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-2 lg:items-center">
        <section class="space-y-6">
            <span class="inline-flex rounded-full bg-sky-100 px-4 py-2 text-sm font-semibold text-sky-700">Multi-tenant booking system</span>
            <div class="space-y-4">
                <h1 class="text-4xl font-black tracking-tight text-slate-900 sm:text-6xl">BookFlow keeps every tenant isolated and simple to manage.</h1>
                <p class="max-w-xl text-lg leading-8 text-slate-600">Admin, shopkeeper, and customer flows all share the same SQLite-backed core. Clean slugs, responsive booking pages, and a demo payment flow are included out of the box.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-3xl border border-white bg-white/80 p-4 shadow-sm">
                    <div class="text-2xl font-black text-slate-900">3</div>
                    <div class="text-sm text-slate-500">roles</div>
                </div>
                <div class="rounded-3xl border border-white bg-white/80 p-4 shadow-sm">
                    <div class="text-2xl font-black text-slate-900">SQLite</div>
                    <div class="text-sm text-slate-500">file based DB</div>
                </div>
                <div class="rounded-3xl border border-white bg-white/80 p-4 shadow-sm">
                    <div class="text-2xl font-black text-slate-900">Demo</div>
                    <div class="text-sm text-slate-500">payment button</div>
                </div>
            </div>
        </section>
        <section class="rounded-[2rem] border border-white/80 bg-white p-6 shadow-2xl shadow-slate-200/60">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Sign in</h2>
                <p class="mt-1 text-sm text-slate-500">Use the seeded admin account or any shopkeeper account from seed.php.</p>
            </div>
            <form method="post" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                    <input name="email" type="email" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none ring-0 focus:border-slate-400" placeholder="admin@bookflow.com">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                    <input name="password" type="password" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none ring-0 focus:border-slate-400" placeholder="••••••••">
                </div>
                <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-700">Login</button>
            </form>
            <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                <div class="font-semibold text-slate-800">Demo accounts</div>
                <div class="mt-2 space-y-1">
                    <div>Admin: admin@bookflow.com / admin123</div>
                    <div>Shopkeepers: created by seed.php or /register</div>
                </div>
            </div>
        </section>
    </div>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

if ($route === '/register') {
    if (is_logged_in()) {
        safe_redirect_after_login();
    }

    if (is_post()) {
        verify_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $businessName = trim((string) ($_POST['business_name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));

        if ($email === '' || $password === '' || $businessName === '') {
            flash('error', 'Business name, email, and password are required.');
            redirect_to('/register');
        }

        if (strlen($password) < 6) {
            flash('error', 'Password must be at least 6 characters.');
            redirect_to('/register');
        }

        if (fetch_user_by_email($email)) {
            flash('error', 'That email is already in use.');
            redirect_to('/register');
        }

        $user = create_shopkeeper_account($email, $password, $businessName, $slug !== '' ? $slug : $businessName);
        ensure_default_working_hours((int) $user['id']);
        $_SESSION['user_id'] = (int) $user['id'];
        flash('success', 'Account created successfully.');
        redirect_to('/shopkeeper/dashboard.php');
    }

    $pageTitle = 'Register';
    require __DIR__ . '/templates/header.php';
    ?>
    <div class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-2 lg:items-center">
        <section class="space-y-6">
            <span class="inline-flex rounded-full bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-700">Shopkeeper onboarding</span>
            <h1 class="text-4xl font-black tracking-tight text-slate-900 sm:text-6xl">Create a business profile and get a public booking slug.</h1>
            <p class="max-w-xl text-lg leading-8 text-slate-600">Each shopkeeper gets isolated services, working hours, and bookings. The system automatically provisions a clean slug such as /salon-123.</p>
        </section>
        <section class="rounded-[2rem] border border-white/80 bg-white p-6 shadow-2xl shadow-slate-200/60">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-slate-900">Register shopkeeper</h2>
            </div>
            <form method="post" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Business name</label>
                    <input name="business_name" type="text" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-slate-400" placeholder="Ahmed Salon">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Custom slug</label>
                    <input name="slug" type="text" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-slate-400" placeholder="ahmed-salon">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Email</label>
                    <input name="email" type="email" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-slate-400" placeholder="owner@example.com">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                    <input name="password" type="password" required minlength="6" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-slate-400" placeholder="At least 6 characters">
                </div>
                <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-700">Create account</button>
            </form>
        </section>
    </div>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

if (str_starts_with($route, '/admin')) {
    if ($route === '/admin') {
        redirect_to('/admin/dashboard.php');
    }
    require_role('admin');
    $file = __DIR__ . $route;
    if (is_file($file)) {
        require $file;
        exit;
    }

    $file = __DIR__ . $route . '.php';
    if (!is_file($file)) {
        http_response_code(404);
        exit('Page not found');
    }
    require $file;
    exit;
}

if (str_starts_with($route, '/shopkeeper')) {
    if ($route === '/shopkeeper') {
        redirect_to('/shopkeeper/dashboard.php');
    }
    require_role('shopkeeper');
    $file = __DIR__ . $route;
    if (is_file($file)) {
        require $file;
        exit;
    }

    $file = __DIR__ . $route . '.php';
    if (!is_file($file)) {
        http_response_code(404);
        exit('Page not found');
    }
    require $file;
    exit;
}

if ($route === '/public/booking.php') {
    require __DIR__ . '/public/booking.php';
    exit;
}

if ($slug = slug_from_path($route)) {
    $_GET['slug'] = $slug;
    require __DIR__ . '/public/booking.php';
    exit;
}

http_response_code(404);
echo 'Page not found';
