<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    die('Invalid CSRF token.');
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);

header('Location: index.php');
exit;