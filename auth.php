<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function authenticate_user(string $email, string $password): bool
{
    $user = fetch_user_by_email($email);
    if (!$user || (int) $user['is_active'] !== 1) {
        return false;
    }

    if (!password_verify($password, (string) $user['password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'role' => (string) $user['role'],
        'email' => (string) $user['email'],
        'business_name' => (string) ($user['business_name'] ?? ''),
        'slug' => (string) ($user['slug'] ?? ''),
    ];
    $_SESSION['user_id'] = (int) $user['id'];
    return true;
}

function create_shopkeeper_account(string $email, string $password, string $businessName, ?string $slug = null): array
{
    $db = db();
    $slug = unique_slug($db, $slug ? slugify($slug) : slugify($businessName));
    $stmt = $db->prepare('INSERT INTO users (email, password, role, business_name, slug, is_active) VALUES (:email, :password, :role, :business_name, :slug, 1)');
    $stmt->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
    $stmt->bindValue(':password', password_hash($password, PASSWORD_BCRYPT), SQLITE3_TEXT);
    $stmt->bindValue(':role', 'shopkeeper', SQLITE3_TEXT);
    $stmt->bindValue(':business_name', trim($businessName), SQLITE3_TEXT);
    $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
    $stmt->execute();

    $id = $db->lastInsertRowID();
    return fetch_user_by_email(strtolower(trim($email))) ?: ['id' => $id, 'slug' => $slug];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}
