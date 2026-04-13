<?php
ini_set('session.cookie_httponly',  1);
ini_set('session.cookie_samesite',  'Strict');
ini_set('session.cookie_secure',    1);
ini_set('session.use_strict_mode',  1);
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime',   1800);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id'])
        && !empty($_SESSION['ua_hash'])
        && hash_equals($_SESSION['ua_hash'], sha1($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login?ref=protected');
        exit;
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
        session_unset();
        session_destroy();
        header('Location: /login?msg=timeout');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function validatePassword(string $pass): array {
    $errors = [];
    if (strlen($pass) < 8)                                        $errors[] = "Minimal 8 karakter";
    if (strlen($pass) > 72)                                       $errors[] = "Maksimal 72 karakter";
    if (!preg_match('/[A-Z]/', $pass))                            $errors[] = "Minimal 1 huruf besar";
    if (!preg_match('/[a-z]/', $pass))                            $errors[] = "Minimal 1 huruf kecil";
    if (!preg_match('/[0-9]/', $pass))                            $errors[] = "Minimal 1 angka";
    if (!preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/', $pass)) $errors[] = "Minimal 1 simbol";
    return $errors;
}

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
