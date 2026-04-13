<?php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'earthquake_auth');
define('DB_USER', getenv('DB_USER') ?: 'ney');
define('DB_PASS', getenv('DB_PASS') ?: 'Daniel_27.');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("[DB ERROR] " . $e->getMessage());
    die("Terjadi kesalahan pada sistem. Silakan coba lagi nanti.");
}
