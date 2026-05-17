<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const APP_NAME = 'BookFlow';
const APP_TIMEZONE = 'UTC';
const DB_FILE = __DIR__ . '/bookflow.db';
const CSRF_SESSION_KEY = 'csrf_token';

if (!class_exists('SQLite3')) {
    if (!defined('SQLITE3_ASSOC')) {
        define('SQLITE3_ASSOC', 1);
    }
    if (!defined('SQLITE3_NUM')) {
        define('SQLITE3_NUM', 2);
    }
    if (!defined('SQLITE3_BOTH')) {
        define('SQLITE3_BOTH', 3);
    }
    if (!defined('SQLITE3_INTEGER')) {
        define('SQLITE3_INTEGER', 1);
    }
    if (!defined('SQLITE3_FLOAT')) {
        define('SQLITE3_FLOAT', 2);
    }
    if (!defined('SQLITE3_TEXT')) {
        define('SQLITE3_TEXT', 3);
    }
    if (!defined('SQLITE3_NULL')) {
        define('SQLITE3_NULL', 4);
    }

    class SQLite3Result
    {
        private ?PDOStatement $statement;

        public function __construct(?PDOStatement $statement)
        {
            $this->statement = $statement;
        }

        public function fetchArray(int $mode = SQLITE3_BOTH): array|false
        {
            if (!$this->statement) {
                return false;
            }

            $row = $this->statement->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                return false;
            }

            if ($mode === SQLITE3_NUM) {
                return array_values($row);
            }

            return $row;
        }
    }

    class SQLite3Stmt
    {
        private PDOStatement $statement;
        private array $bindings = [];

        public function __construct(PDO $pdo, string $sql)
        {
            $this->statement = $pdo->prepare($sql);
        }

        public function bindValue(string|int $param, mixed $value, int $type = SQLITE3_TEXT): bool
        {
            $this->bindings[(string) $param] = [$value, $type];
            return true;
        }

        private function pdoType(int $type): int
        {
            return match ($type) {
                SQLITE3_INTEGER => PDO::PARAM_INT,
                SQLITE3_NULL => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
        }

        public function execute(): SQLite3Result
        {
            foreach ($this->bindings as $param => [$value, $type]) {
                $this->statement->bindValue($param, $value, $this->pdoType($type));
            }

            $this->statement->execute();
            return new SQLite3Result($this->statement);
        }
    }

    class SQLite3
    {
        private PDO $pdo;

        public function __construct(string $filename)
        {
            $this->pdo = new PDO('sqlite:' . $filename);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        public function busyTimeout(int $milliseconds): void
        {
            $seconds = max(1, (int) ceil($milliseconds / 1000));
            $this->pdo->setAttribute(PDO::ATTR_TIMEOUT, $seconds);
        }

        public function exec(string $sql): bool
        {
            return $this->pdo->exec($sql) !== false;
        }

        public function querySingle(string $sql, bool $entireRow = false): array|int|string|float|null
        {
            $statement = $this->pdo->query($sql);
            if (!$statement) {
                return $entireRow ? [] : null;
            }

            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if ($row === false) {
                return $entireRow ? [] : null;
            }

            if ($entireRow) {
                return $row;
            }

            return count($row) > 0 ? array_values($row)[0] : null;
        }

        public function prepare(string $sql): SQLite3Stmt
        {
            return new SQLite3Stmt($this->pdo, $sql);
        }

        public function query(string $sql): SQLite3Result
        {
            $statement = $this->pdo->query($sql);
            return new SQLite3Result($statement ?: null);
        }

        public function lastInsertRowID(): int
        {
            return (int) $this->pdo->lastInsertId();
        }
    }
}

if (!date_default_timezone_set(APP_TIMEZONE)) {
    date_default_timezone_set('UTC');
}

function db(): SQLite3
{
    static $db = null;

    if ($db instanceof SQLite3) {
        return $db;
    }

    $db = new SQLite3(DB_FILE);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('PRAGMA journal_mode = WAL');

    initialize_database($db);
    ensure_demo_shopkeeper_account($db);

    if (!defined('BOOKFLOW_AUTO_SEEDING') && should_auto_seed($db)) {
        define('BOOKFLOW_AUTO_SEEDING', true);
        require __DIR__ . '/seed.php';
    }

    return $db;
}

function should_auto_seed(SQLite3 $db): bool
{
    $script = basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    if ($script === 'seed.php') {
        return false;
    }

    $result = $db->query('SELECT id FROM users WHERE role = "admin" LIMIT 1');
    return $result->fetchArray(SQLITE3_ASSOC) === false;
}

function ensure_demo_shopkeeper_account(SQLite3 $db): void
{
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->bindValue(':email', 'shopkeeper@bookflow.com', SQLITE3_TEXT);
    if ($stmt->execute()->fetchArray(SQLITE3_ASSOC)) {
        return;
    }

    $insert = $db->prepare('INSERT INTO users (email, password, role, business_name, slug, is_active, dummy_payment_enabled) VALUES (:email, :password, :role, :business_name, :slug, 1, 1)');
    $insert->bindValue(':email', 'shopkeeper@bookflow.com', SQLITE3_TEXT);
    $insert->bindValue(':password', password_hash('shop123', PASSWORD_BCRYPT), SQLITE3_TEXT);
    $insert->bindValue(':role', 'shopkeeper', SQLITE3_TEXT);
    $insert->bindValue(':business_name', 'Demo Shopkeeper', SQLITE3_TEXT);
    $insert->bindValue(':slug', 'demo-shopkeeper', SQLITE3_TEXT);
    $insert->execute();

    ensure_default_working_hours((int) $db->lastInsertRowID());
}

function initialize_database(SQLite3 $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ("admin", "shopkeeper")),
            business_name TEXT,
            slug TEXT UNIQUE,
            is_active INTEGER NOT NULL DEFAULT 1,
            dummy_payment_enabled INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $db->exec(
        'CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            shopkeeper_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            duration_minutes INTEGER NOT NULL,
            price REAL NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(shopkeeper_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $db->exec(
        'CREATE TABLE IF NOT EXISTS working_hours (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            shopkeeper_id INTEGER NOT NULL,
            day_of_week INTEGER NOT NULL CHECK(day_of_week BETWEEN 0 AND 6),
            start_time TEXT NOT NULL,
            end_time TEXT NOT NULL,
            is_closed INTEGER NOT NULL DEFAULT 0,
            UNIQUE(shopkeeper_id, day_of_week),
            FOREIGN KEY(shopkeeper_id) REFERENCES users(id) ON DELETE CASCADE
        )'
    );

    $db->exec(
        'CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            shopkeeper_id INTEGER NOT NULL,
            service_id INTEGER NOT NULL,
            customer_name TEXT NOT NULL,
            customer_email TEXT NOT NULL,
            customer_phone TEXT,
            booking_date DATE NOT NULL,
            start_time TEXT NOT NULL,
            end_time TEXT NOT NULL,
            status TEXT NOT NULL CHECK(status IN ("pending", "confirmed", "cancelled", "completed")),
            paid_dummy INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(shopkeeper_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(service_id) REFERENCES services(id) ON DELETE CASCADE
        )'
    );

    $db->exec(
        'CREATE TABLE IF NOT EXISTS time_slots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            shopkeeper_id INTEGER NOT NULL,
            service_id INTEGER NOT NULL,
            booking_date DATE NOT NULL,
            start_time TEXT NOT NULL,
            end_time TEXT NOT NULL,
            is_booked INTEGER NOT NULL DEFAULT 0,
            UNIQUE(shopkeeper_id, service_id, booking_date, start_time),
            FOREIGN KEY(shopkeeper_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(service_id) REFERENCES services(id) ON DELETE CASCADE
        )'
    );

    $db->exec('CREATE INDEX IF NOT EXISTS idx_bookings_shopkeeper_date ON bookings(shopkeeper_id, booking_date)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_services_shopkeeper ON services(shopkeeper_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_working_hours_shopkeeper_day ON working_hours(shopkeeper_id, day_of_week)');

}

function app_base_path(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptName = preg_replace('#/+#', '/', $scriptName) ?: '/';

    if (preg_match('#/(admin|shopkeeper|public)(/|$)#', $scriptName)) {
        return '';
    }

    $scriptBase = basename($scriptName);
    if (!in_array($scriptBase, ['index.php', 'router.php'], true)) {
        return '';
    }

    $basePath = preg_replace('#/[^/]+$#', '', $scriptName) ?: '';
    $basePath = trim($basePath, '/');

    if ($basePath === '' || $basePath === '.') {
        return '';
    }

    return '/' . trim($basePath, '/');
}

function app_url(string $path = ''): string
{
    $scheme = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $path = '/' . ltrim($path, '/');
    $basePath = app_base_path();

    return $scheme . '://' . $host . $basePath . ($path === '/' ? '' : $path);
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = app_base_path();

    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }

    return '/' . trim($uri, '/');
}

function redirect_to(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}
