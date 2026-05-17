<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this script from the command line: php seed.php\n");
}

$db = db();
$db->exec('PRAGMA foreign_keys = OFF');
$db->exec('DELETE FROM bookings');
$db->exec('DELETE FROM services');
$db->exec('DELETE FROM working_hours');
$db->exec('DELETE FROM users WHERE role = "shopkeeper"');
$db->exec('DELETE FROM users WHERE role = "admin"');
$db->exec('DELETE FROM sqlite_sequence WHERE name IN ("bookings", "services", "working_hours", "users")');
$db->exec('PRAGMA foreign_keys = ON');

$adminStmt = $db->prepare('INSERT INTO users (email, password, role, business_name, slug, is_active, dummy_payment_enabled) VALUES (:email, :password, :role, :business_name, :slug, 1, 1)');
$adminStmt->bindValue(':email', 'admin@bookflow.com', SQLITE3_TEXT);
$adminStmt->bindValue(':password', password_hash('admin123', PASSWORD_BCRYPT), SQLITE3_TEXT);
$adminStmt->bindValue(':role', 'admin', SQLITE3_TEXT);
$adminStmt->bindValue(':business_name', null, SQLITE3_NULL);
$adminStmt->bindValue(':slug', null, SQLITE3_NULL);
$adminStmt->execute();

$businessPrefixes = ['Salon', 'Spa', 'Barber', 'Studio', 'Clinic', 'Lounge', 'Beauty Bar', 'Glow Room', 'Trim House', 'Wellness'];
$servicesPool = [
    ['Haircut', 30, 25],
    ['Facial', 45, 40],
    ['Beard Trim', 20, 15],
    ['Manicure', 35, 20],
    ['Pedicure', 40, 30],
    ['Massage', 60, 60],
    ['Hair Coloring', 90, 80],
    ['Waxing', 25, 22],
    ['Makeup', 50, 55],
    ['Consultation', 15, 10],
];
$customerNames = ['Amina Khan', 'Sara Ali', 'John Smith', 'Fatima Noor', 'Omar Hassan', 'Leah Brown', 'Ahmed Raza', 'Mariam Saleh', 'Daniel Lee', 'Hina Shah'];
$statuses = ['pending', 'confirmed', 'completed', 'cancelled'];

for ($i = 0; $i < 5; $i++) {
    $businessName = $businessPrefixes[array_rand($businessPrefixes)] . ' ' . random_int(100, 999);
    $email = strtolower(str_replace(' ', '.', $businessName)) . '@bookflow.com';
    $slug = unique_slug($db, slugify($businessName));
    $password = 'shop123';

    $stmt = $db->prepare('INSERT INTO users (email, password, role, business_name, slug, is_active, dummy_payment_enabled) VALUES (:email, :password, "shopkeeper", :business_name, :slug, 1, 1)');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':password', password_hash($password, PASSWORD_BCRYPT), SQLITE3_TEXT);
    $stmt->bindValue(':business_name', $businessName, SQLITE3_TEXT);
    $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
    $stmt->execute();

    $shopkeeperId = (int) $db->lastInsertRowID();
    ensure_default_working_hours($shopkeeperId);

    $serviceCount = random_int(3, 5);
    $selectedPoolKeys = array_rand($servicesPool, $serviceCount);
    $selectedPoolKeys = is_array($selectedPoolKeys) ? $selectedPoolKeys : [$selectedPoolKeys];

    foreach ($selectedPoolKeys as $poolKey) {
        [$serviceName, $duration, $price] = $servicesPool[$poolKey];
        $serviceStmt = $db->prepare('INSERT INTO services (shopkeeper_id, name, duration_minutes, price, is_active) VALUES (:shopkeeper_id, :name, :duration_minutes, :price, 1)');
        $serviceStmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
        $serviceStmt->bindValue(':name', $serviceName, SQLITE3_TEXT);
        $serviceStmt->bindValue(':duration_minutes', $duration, SQLITE3_INTEGER);
        $serviceStmt->bindValue(':price', $price, SQLITE3_FLOAT);
        $serviceStmt->execute();
    }

    $serviceResult = $db->prepare('SELECT * FROM services WHERE shopkeeper_id = :shopkeeper_id');
    $serviceResult->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
    $services = [];
    $cursor = $serviceResult->execute();
    while ($row = $cursor->fetchArray(SQLITE3_ASSOC)) {
        $services[] = $row;
    }

    $bookingCount = random_int(10, 20);
    for ($j = 0; $j < $bookingCount; $j++) {
        $service = $services[array_rand($services)];
        $date = date('Y-m-d', strtotime((random_int(0, 1) ? '+' : '-') . random_int(1, 30) . ' days'));
        $slots = generateTimeSlots($shopkeeperId, (int) $service['id'], $date);
        if (!$slots) {
            continue;
        }

        $startTime = $slots[array_rand($slots)];
        $endTime = booking_end_time($startTime, (int) $service['duration_minutes']);
        $status = $statuses[array_rand($statuses)];
        $paidDummy = in_array($status, ['confirmed', 'completed'], true) ? 1 : 0;
        $customerName = $customerNames[array_rand($customerNames)];
        $customerEmail = strtolower(str_replace(' ', '.', $customerName)) . '+' . random_int(1, 999) . '@example.com';

        $bookingStmt = $db->prepare('INSERT INTO bookings (shopkeeper_id, service_id, customer_name, customer_email, customer_phone, booking_date, start_time, end_time, status, paid_dummy)
            VALUES (:shopkeeper_id, :service_id, :customer_name, :customer_email, :customer_phone, :booking_date, :start_time, :end_time, :status, :paid_dummy)');
        $bookingStmt->bindValue(':shopkeeper_id', $shopkeeperId, SQLITE3_INTEGER);
        $bookingStmt->bindValue(':service_id', (int) $service['id'], SQLITE3_INTEGER);
        $bookingStmt->bindValue(':customer_name', $customerName, SQLITE3_TEXT);
        $bookingStmt->bindValue(':customer_email', $customerEmail, SQLITE3_TEXT);
        $bookingStmt->bindValue(':customer_phone', '03' . random_int(100000000, 999999999), SQLITE3_TEXT);
        $bookingStmt->bindValue(':booking_date', $date, SQLITE3_TEXT);
        $bookingStmt->bindValue(':start_time', $startTime, SQLITE3_TEXT);
        $bookingStmt->bindValue(':end_time', $endTime, SQLITE3_TEXT);
        $bookingStmt->bindValue(':status', $status, SQLITE3_TEXT);
        $bookingStmt->bindValue(':paid_dummy', $paidDummy, SQLITE3_INTEGER);
        $bookingStmt->execute();
    }
}

echo "Seed completed successfully.\n";
echo "Admin login: admin@bookflow.com / admin123\n";
echo "Shopkeeper logins: seed-generated emails with password shop123\n";
