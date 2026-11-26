
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Session protection
requireAuth();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// CSRF protection
$token = $_POST['csrf_token'] ?? null;

if (!is_string($token) || !validateCsrfToken($token)) {
    http_response_code(403);
    exit('Invalid security token.');
}

// Validate book ID
$id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id <= 0) {
    header('Location: index.php');
    exit;
}

try {

    // Prepared statement prevents SQL injection
    $stmt = $mysqli->prepare(
        "DELETE FROM books WHERE id = ?"
    );

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        throw new RuntimeException('Delete failed.');
    }

    $stmt->close();

    header('Location: index.php');
    exit;

} catch (Throwable $exception) {

    error_log($exception->getMessage());

    http_response_code(500);
    exit('Unable to delete book.');

}
