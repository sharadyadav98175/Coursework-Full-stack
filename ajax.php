
<?php
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/db.php';

$mysqli->set_charset("utf8mb4");

$search = $_GET['search'] ?? '';

if (!is_string($search)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid search']);
    exit;
}

$search = '%' . trim($search) . '%';

$stmt = $mysqli->prepare(
    "SELECT id, title, author, genre, year, summary
     FROM books
     WHERE title LIKE ?
     ORDER BY title ASC"
);

$stmt->bind_param("s", $search);
$stmt->execute();

$result = $stmt->get_result();

$books = [];

while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

echo json_encode($books);

$stmt->close();
