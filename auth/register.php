<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login?tab=register');
    exit;
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? null)) {
    header('Location: /login?error=csrf&tab=register');
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username']  ?? '');
$email     = trim($_POST['email']     ?? '');
$password  = $_POST['password']       ?? '';
$confirm   = $_POST['confirm_password'] ?? '';

if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
    header('Location: /login?error=empty&tab=register'); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /login?error=bademail&tab=register'); exit;
}
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    header('Location: /login?error=badusername&tab=register'); exit;
}
if (!empty(validatePassword($password))) {
    header('Location: /login?error=weakpass&tab=register'); exit;
}
if ($password !== $confirm) {
    header('Location: /login?error=passmatch&tab=register'); exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    header('Location: /login?error=exists&tab=register'); exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$pdo->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)")->execute([$full_name, $username, $email, $hash]);

header('Location: /login?success=registered&tab=login');
exit;
