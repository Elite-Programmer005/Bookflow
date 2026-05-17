<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION[CSRF_SESSION_KEY];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $value = (string) $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function user_from_session(): ?array
{
    $sessionUserId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
    if ($sessionUserId <= 0) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $sessionUserId, SQLITE3_INTEGER);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($result && (int) ($result['is_active'] ?? 1) !== 1) {
        return null;
    }

    return $result ?: null;
}

function current_user(): ?array
{
    return user_from_session();
}

function current_user_id(): ?int
{
    $user = current_user();
    return $user ? (int) $user['id'] : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? null) === 'admin';
}

function is_shopkeeper(): bool
{
    return (current_user()['role'] ?? null) === 'shopkeeper';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please sign in first.');
        redirect_to('/login');
    }
}

function require_role(string $role): void
{
    require_login();
    if ((current_user()['role'] ?? null) !== $role) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function safe_redirect_after_login(): void
{
    $user = current_user();
    if (!$user) {
        redirect_to('/login');
    }

    if ($user['role'] === 'admin') {
        redirect_to('/admin/dashboard');
    }

    redirect_to('/shopkeeper/dashboard');
}

function route_exists(string $path): bool
{
    static $reserved = [
        '/login', '/logout', '/register', '/admin', '/shopkeeper', '/public', '/assets', '/seed.php', '/index.php', '/favicon.ico'
    ];

    foreach ($reserved as $prefix) {
        if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
            return true;
        }
    }

    return false;
}

function slug_from_path(string $path): ?string
{
    $normalized = trim($path, '/');
    if ($normalized === '' || str_contains($normalized, '/')) {
        return null;
    }

    return route_exists('/' . $normalized) ? null : $normalized;
}

function fetch_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $row ?: null;
}

function fetch_user_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE slug = :slug AND role = "shopkeeper" LIMIT 1');
    $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return $row ?: null;
}

function fetch_services(int $shopkeeperId, bool $onlyActive = true): array
{
    $sql = 'SELECT * FROM services WHERE shopkeeper_id = :shopkeeper_id';
    if ($onlyActive) {
        $sql .= ' AND is_active = 1';
    }
    $sql .= ' ORDER BY created_at DESC, id DESC';

    $stmt = db()->prepare($sql);
    $stmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }

    return $rows;
}

function fetch_working_hours(int $shopkeeperId): array
{
    $stmt = db()->prepare('SELECT * FROM working_hours WHERE shopkeeper_id = :shopkeeper_id ORDER BY day_of_week ASC');
    $stmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[(int) $row['day_of_week']] = $row;
    }

    return $rows;
}

function fetch_bookings_for_shopkeeper(int $shopkeeperId, ?string $date = null): array
{
    $sql = 'SELECT b.*, s.name AS service_name, s.duration_minutes, s.price, u.business_name
            FROM bookings b
            JOIN services s ON s.id = b.service_id
            JOIN users u ON u.id = b.shopkeeper_id
            WHERE b.shopkeeper_id = :shopkeeper_id';

    if ($date !== null) {
        $sql .= ' AND b.booking_date = :booking_date';
    }

    $sql .= ' ORDER BY b.booking_date ASC, b.start_time ASC, b.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
    if ($date !== null) {
        $stmt->bindValue(':booking_date', $date, SQLITE3_TEXT);
    }

    $result = $stmt->execute();
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }

    return $rows;
}

function fetch_all_bookings(): array
{
    $result = db()->query('SELECT b.*, s.name AS service_name, u.business_name, u.slug
        FROM bookings b
        JOIN services s ON s.id = b.service_id
        JOIN users u ON u.id = b.shopkeeper_id
        ORDER BY b.booking_date DESC, b.start_time DESC, b.id DESC');

    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }

    return $rows;
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'confirmed' => 'bg-emerald-100 text-emerald-700',
        'completed' => 'bg-sky-100 text-sky-700',
        'cancelled' => 'bg-rose-100 text-rose-700',
        default => 'bg-amber-100 text-amber-700',
    };
}

function day_name(int $day): string
{
    return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$day] ?? 'Unknown';
}

function minutes_between(string $start, string $end): int
{
    return max(0, (int) (((strtotime($end) - strtotime($start)) / 60)));
}

function add_minutes(string $time, int $minutes): string
{
    return date('H:i', strtotime($time) + ($minutes * 60));
}

function booking_end_time(string $start, int $durationMinutes): string
{
    return add_minutes($start, $durationMinutes);
}

function validate_booking_date(string $date): bool
{
    $timestamp = strtotime($date);
    return $timestamp !== false && $timestamp >= strtotime(date('Y-m-d'));
}

function generateTimeSlots(int $shopkeeper_id, int $service_id, string $date): array
{
    $serviceStmt = db()->prepare('SELECT * FROM services WHERE id = :id AND shopkeeper_id = :shopkeeper_id AND is_active = 1 LIMIT 1');
    $serviceStmt->bindValue(':id', $service_id, SQLITE3_INTEGER);
    $serviceStmt->bindValue(':shopkeeper_id', $shopkeeper_id, SQLITE3_INTEGER);
    $service = $serviceStmt->execute()->fetchArray(SQLITE3_ASSOC);
    if (!$service) {
        return [];
    }

    $dayOfWeek = (int) date('N', strtotime($date)) - 1;
    $hoursStmt = db()->prepare('SELECT * FROM working_hours WHERE shopkeeper_id = :shopkeeper_id AND day_of_week = :day_of_week LIMIT 1');
    $hoursStmt->bindValue(':shopkeeper_id', $shopkeeper_id, SQLITE3_INTEGER);
    $hoursStmt->bindValue(':day_of_week', $dayOfWeek, SQLITE3_INTEGER);
    $hours = $hoursStmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$hours || (int) $hours['is_closed'] === 1) {
        return [];
    }

    $duration = max(15, (int) $service['duration_minutes']);
    $start = strtotime($date . ' ' . $hours['start_time']);
    $end = strtotime($date . ' ' . $hours['end_time']);

    $existingStmt = db()->prepare('SELECT start_time, end_time FROM bookings WHERE shopkeeper_id = :shopkeeper_id AND booking_date = :booking_date AND status != "cancelled"');
    $existingStmt->bindValue(':shopkeeper_id', $shopkeeper_id, SQLITE3_INTEGER);
    $existingStmt->bindValue(':booking_date', $date, SQLITE3_TEXT);
    $existingResult = $existingStmt->execute();
    $existing = [];
    while ($row = $existingResult->fetchArray(SQLITE3_ASSOC)) {
        $existing[] = $row;
    }

    $slots = [];
    for ($cursor = $start; ($cursor + ($duration * 60)) <= $end; $cursor += ($duration * 60)) {
        $slotStart = date('H:i', $cursor);
        $slotEnd = date('H:i', $cursor + ($duration * 60));

        $conflict = false;
        foreach ($existing as $booking) {
            if ($slotStart < $booking['end_time'] && $slotEnd > $booking['start_time']) {
                $conflict = true;
                break;
            }
        }

        if (!$conflict) {
            $slots[] = $slotStart;
        }
    }

    return $slots;
}

function ensure_shopkeeper_ownership(int $shopkeeperId): void
{
    $user = current_user();
    if (!$user) {
        require_login();
    }

    if (($user['role'] ?? null) === 'admin') {
        return;
    }

    if ((int) $user['id'] !== $shopkeeperId) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : bin2hex(random_bytes(3));
}

function unique_slug(SQLite3 $db, string $base): string
{
    $slug = $base;
    $counter = 1;

    while (true) {
        $stmt = $db->prepare('SELECT COUNT(*) AS count FROM users WHERE slug = :slug');
        $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if ((int) ($row['count'] ?? 0) === 0) {
            return $slug;
        }
        $slug = $base . '-' . $counter;
        $counter++;
    }
}

function render_page_title(string $title): string
{
    return $title . ' | ' . APP_NAME;
}

function business_label(array $user): string
{
    return $user['business_name'] ?: 'BookFlow Business';
}

function ensure_default_working_hours(int $shopkeeperId): void
{
    $defaults = [
        0 => ['09:00', '17:00', 0],
        1 => ['09:00', '17:00', 0],
        2 => ['09:00', '17:00', 0],
        3 => ['09:00', '17:00', 0],
        4 => ['09:00', '17:00', 0],
        5 => ['10:00', '14:00', 0],
        6 => ['00:00', '00:00', 1],
    ];

    $db = db();
    $stmt = $db->prepare('INSERT INTO working_hours (shopkeeper_id, day_of_week, start_time, end_time, is_closed)
        VALUES (:shopkeeper_id, :day_of_week, :start_time, :end_time, :is_closed)
        ON CONFLICT(shopkeeper_id, day_of_week) DO UPDATE SET
            start_time = excluded.start_time,
            end_time = excluded.end_time,
            is_closed = excluded.is_closed');

    foreach ($defaults as $day => [$start, $end, $closed]) {
        $stmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
        $stmt->bindValue(':day_of_week', $day, SQLITE3_INTEGER);
        $stmt->bindValue(':start_time', $start, SQLITE3_TEXT);
        $stmt->bindValue(':end_time', $end, SQLITE3_TEXT);
        $stmt->bindValue(':is_closed', $closed, SQLITE3_INTEGER);
        $stmt->execute();
    }
}
