<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']);

if ($isLocal) {
    $host = 'localhost';
    $database = 'sportshub';
    $username = 'root';
    $password = '';
} else {
    $host = getenv('DB_HOST') ?: 'YOUR_DATABASE_HOST';
    $database = getenv('DB_NAME') ?: 'YOUR_DATABASE_NAME';
    $username = getenv('DB_USER') ?: 'YOUR_DATABASE_USER';
    $password = getenv('DB_PASS') ?: 'YOUR_DATABASE_PASSWORD';
}
try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $error) {
    http_response_code(503);
    exit(
        'Database connection failed. Import database.sql and configure ' .
        'DB_HOST, DB_NAME, DB_USER and DB_PASS.'
    );
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('csrf')) {
    function csrf(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf'];
    }
}

if (!function_exists('check_csrf')) {
    function check_csrf(): void
    {
        $sessionToken = $_SESSION['csrf'] ?? '';
        $formToken = $_POST['csrf'] ?? '';

        if (!hash_equals($sessionToken, $formToken)) {
            http_response_code(400);
            exit('Invalid request token.');
        }
    }
}

if (!function_exists('require_login')) {
    function require_login(): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: login.php');
            exit;
        }
    }
}
