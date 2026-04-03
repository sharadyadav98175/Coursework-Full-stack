<?php
declare(strict_types=1);

require_once __DIR__ . '/../and/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getPDO();
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT id, title, author, genre, year, summary
        FROM books
        WHERE title LIKE :search
        ORDER BY year DESC, title ASC
    ");
    $stmt->execute([
        ':search' => '%' . $search . '%'
    ]);
} else {
    $stmt = $pdo->query("
        SELECT id, title, author, genre, year, summary
        FROM books
        ORDER BY year DESC, title ASC
    ");
}

echo json_encode($stmt->fetchAll());