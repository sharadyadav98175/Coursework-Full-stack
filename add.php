<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

requireAuth();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $year = (int)($_POST['year'] ?? 0);
        $summary = trim($_POST['summary'] ?? '');

        if ($title === '' || $author === '' || $genre === '' || $year <= 0) {
            $errors[] = 'Please fill all required fields with valid values.';
        } else {
            $pdo = getPDO();
            $stmt = $pdo->prepare('
                INSERT INTO books (title, author, genre, year, summary)
                VALUES (:title, :author, :genre, :year, :summary)
            ');
            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':genre' => $genre,
                ':year' => $year,
                ':summary' => $summary,
            ]);
            $success = true;
        }
    }
}

$token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width:700px;">
    <h2>Add Book</h2>

    <?php if ($errors): ?>
        <div class="message error">
            <?php foreach ($errors as $e): ?>
                <div><?php echo htmlspecialchars((string)$e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success">
            Book added successfully. <a href="index.php">Back to list</a>
        </div>
    <?php endif; ?>

    <form method="post" action="add.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">

        <div class="form-row">
            <label>Title</label>
            <input type="text" name="title" required>
        </div>

        <div class="form-row">
            <label>Author</label>
            <input type="text" name="author" required>
        </div>

        <div class="form-row">
            <label>Genre</label>
            <input type="text" name="genre" required>
        </div>

        <div class="form-row">
            <label>Year</label>
            <input type="number" name="year" min="1" required>
        </div>

        <div class="form-row">
            <label>Summary</label>
            <textarea name="summary" rows="5"></textarea>
        </div>

        <button type="submit">Add Book</button>
        <a class="link-btn" href="index.php">Back</a>
    </form>
</div>
</body>
</html>