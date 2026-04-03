<?php
declare(strict_types=1);
require_once __DIR__ . '/../inc/db.php';
header('Content-Type: application/json; charset=utf-8');

$q = $_GET['q'] ?? '';
$q = trim($q);
if ($q === '') {
    echo json_encode([]);
    exit;
}
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT DISTINCT title FROM books WHERE title LIKE :q ORDER BY title LIMIT 10');
$stmt->execute([':q' => $q . '%']);
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($rows);
