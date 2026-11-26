
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Protect page from unauthorised access
requireAuth();

$mysqli->set_charset("utf8mb4");

// Validate book ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: index.php');
    exit;
}

// Prepared statement to prevent SQL injection
$stmt = $mysqli->prepare(
    "SELECT id, title, author, genre, year, summary
     FROM books
     WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$stmt->bind_result(
    $bookId,
    $title,
    $author,
    $genre,
    $year,
    $summary
);

// Check whether the book exists
if (!$stmt->fetch()) {
    $stmt->close();
    header('Location: index.php');
    exit;
}

$stmt->close();

// Prevent XSS attacks
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>View Book</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container" style="max-width:800px;">

    <h1>Book Details</h1>

    <hr>

    <h2><?= e($title) ?></h2>

    <p>
        <strong>Book ID:</strong>
        <?= e($bookId) ?>
    </p>

    <p>
        <strong>Author:</strong>
        <?= e($author) ?>
    </p>

    <p>
        <strong>Genre:</strong>
        <?= e($genre) ?>
    </p>

    <p>
        <strong>Publication Year:</strong>
        <?= e($year) ?>
    </p>

    <p>
        <strong>Summary:</strong>
    </p>

    <p>
        <?= nl2br(e($summary ?? '')) ?>
    </p>

    <hr>

    <a class="btn"
       href="edit.php?id=<?= (int)$bookId ?>">
        Edit Book
    </a>

    <a class="link-btn"
       href="index.php">
        Back to Library
    </a>

</div>

</body>
</html>
