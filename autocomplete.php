
<?php
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/db.php';

$mysqli->set_charset('utf8mb4');

$q = $_GET['q'] ?? '';

if (!is_string($q) || trim($q) === '') {
    echo json_encode([]);
    exit;
}

$search = trim($q) . '%';

$stmt = $mysqli->prepare(
    "SELECT DISTINCT title
     FROM books
     WHERE title LIKE ?
     ORDER BY title
     LIMIT 10"
);

$stmt->bind_param("s", $search);
$stmt->execute();
$stmt->bind_result($title);

$books = [];

while ($stmt->fetch()) {
    $books[] = $title;
}

$stmt->close();

echo json_encode($books);
