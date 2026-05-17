<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$route = current_path();

if ($route === '/assets' || str_starts_with($route, '/assets/')) {
    http_response_code(404);
    exit('Not found');
}

if ($route === '/' || $route === '') {
    $pageTitle = 'Welcome';
    require __DIR__ . '/templates/landing.php';
    exit;
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
                <div class="field-with-toggle">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                    <div class="field-toggle-wrap">
                        <input id="login_password" name="password" type="password" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none ring-0 focus:border-slate-400" placeholder="••••••••">
                        <button type="button" class="field-toggle-button" data-password-toggle="#login_password">Show</button>
                    </div>
                </div>
                <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-700">Login</button>
            </form>
            <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                <div class="font-semibold text-slate-800">Demo accounts</div>
                <div class="mt-2 space-y-1">
                    <div>Admin: admin@bookflow.com / admin123</div>
                    <div>Shopkeeper: shopkeeper@bookflow.com / shop123</div>
                    <div>Additional shopkeepers are created by seed.php or /register</div>
                </div>
            </div>
        </section>
    </div>
    <?php
    require __DIR__ . '/templates/footer.php';
    exit;
}

if ($route === '/book') {
    $pageTitle = 'Book Services';
    $shopkeepers = fetch_public_shopkeepers();
    require __DIR__ . '/templates/header.php';
    ?>
    <div class="space-y-8">
        <section class="rounded-[2rem] bg-slate-900 p-8 text-white shadow-2xl shadow-slate-300/40 animate-rise">
            <div class="max-w-3xl space-y-4">
                <span class="inline-flex rounded-full bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-sky-200">Customer access</span>
                <h1 class="text-4xl font-black tracking-tight sm:text-5xl animate-soft-float">Book a service without logging in</h1>
                <p class="text-slate-300">Choose a shopkeeper, open their booking page, and pick a time slot. Customer accounts are optional.</p>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($shopkeepers as $shopkeeper): ?>
                <article class="rounded-[2rem] border border-white bg-white p-6 shadow-lg transition duration-200 hover:-translate-y-1 hover:shadow-2xl animate-rise">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900"><?= e((string) $shopkeeper['business_name']) ?></h2>
                            <p class="mt-1 text-sm text-slate-500">Slug: <?= e((string) $shopkeeper['slug']) ?></p>
                        </div>
                        <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"><?= (int) $shopkeeper['service_count'] ?> services</div>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-slate-600">Open the booking page, browse services, and choose a slot that fits your schedule.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="<?= e(app_url('/' . (string) $shopkeeper['slug'])) ?>" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-slate-700 hover:shadow-lg">Book now</a>
                        <a href="<?= e(app_url('/' . (string) $shopkeeper['slug'])) ?>" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:bg-slate-50">View page</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <?php if (!$shopkeepers): ?>
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-slate-600">
                No shopkeepers are available right now.
            </div>
        <?php endif; ?>
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
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'role' => 'shopkeeper',
            'email' => $email,
            'business_name' => $businessName,
            'slug' => (string) ($user['slug'] ?? $slug),
        ];
        $_SESSION['user_id'] = (int) $user['id'];
        flash('success', 'Account created successfully.');
        redirect_to('/shopkeeper/dashboard');
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
                <div class="field-with-toggle">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                    <div class="field-toggle-wrap">
                        <input id="register_password" name="password" type="password" required minlength="6" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-slate-400" placeholder="At least 6 characters">
                        <button type="button" class="field-toggle-button" data-password-toggle="#register_password">Show</button>
                    </div>
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
        redirect_to('/admin/dashboard');
    }
    require_role('admin');
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
        redirect_to('/shopkeeper/dashboard');
    }
    require_role('shopkeeper');
    $file = __DIR__ . $route . '.php';
    if (!is_file($file)) {
        http_response_code(404);
        exit('Page not found');
    }
    require $file;
    exit;
}

if ($route !== '/' && $route !== '') {
    $slug = slug_from_path($route);
    if ($slug !== null) {
        $shopkeeper = fetch_user_by_slug($slug);
        if ($shopkeeper) {
            $_GET['slug'] = $slug;
            require __DIR__ . '/public/booking.php';
            exit;
        }

        http_response_code(404);
        exit('Not found');
    }
}

http_response_code(404);
echo 'Page not found';
