<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SESSION['login_attempts'] >= 5) {
    die('Too many failed login attempts. Try again later.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login-form.php');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['login_error'] = 'Invalid CSRF token.';
    header('Location: login-form.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Please fill in all fields.';
    header('Location: login-form.php');
    exit;
}

$pdo = getPDO();

$stmt = $pdo->prepare('SELECT user_id, username, password FROM users WHERE username = :username LIMIT 1');
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, (string)$user['password'])) {
    $_SESSION['login_attempts']++;
    $_SESSION['login_error'] = 'Invalid username or password.';
    header('Location: login-form.php');
    exit;
}

session_regenerate_id(true);
$_SESSION['loggedin'] = true;
$_SESSION['username'] = $user['username'];
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['login_attempts'] = 0;

header('Location: index.php');
exit;