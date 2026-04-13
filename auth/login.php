<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
    header('Location: /login?error=csrf');
    exit;
}

$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: /login?error=empty');
    exit;
}

$ip   = $_SERVER['REMOTE_ADDR'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$stmt->execute([$ip]);
if ((int)$stmt->fetchColumn() >= 10) {
    header('Location: /login?error=ratelimit');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

$pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)")->execute([$ip, $email]);

if ($user && !empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
    header('Location: /login?error=locked');
    exit;
}

if (!$user || !password_verify($password, $user['password'])) {
    if ($user) {
        $newFailed = (int)$user['failed_attempts'] + 1;
        $lockUntil = $newFailed >= 5
            ? date('Y-m-d H:i:s', strtotime('+15 minutes'))
            : $user['locked_until'];
        $pdo->prepare("UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?")
            ->execute([$newFailed, $lockUntil, $user['id']]);
    }
    header('Location: /login?error=invalid');
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id']       = $user['id'];
$_SESSION['username']      = $user['username'];
$_SESSION['full_name']     = $user['full_name'];
$_SESSION['ua_hash']       = sha1($_SERVER['HTTP_USER_AGENT'] ?? '');
$_SESSION['last_activity'] = time();

$pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?")
    ->execute([$user['id']]);

unset($_SESSION['csrf_token']);
header('Location: /dashboard');
exit;
