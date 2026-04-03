<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

requireAuth();

$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: index.php');
    exit;
}

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
            $errors[] = 'Please fill all required fields.';
        } else {
            $update = $pdo->prepare('
                UPDATE books
                SET title = :title,
                    author = :author,
                    genre = :genre,
                    year = :year,
                    summary = :summary
                WHERE id = :id
            ');
            $update->execute([
                ':title' => $title,
                ':author' => $author,
                ':genre' => $genre,
                ':year' => $year,
                ':summary' => $summary,
                ':id' => $id,
            ]);
            $success = true;

            $stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $book = $stmt->fetch();
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
    <title>Edit Book</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width:700px;">
    <h2>Edit Book</h2>

    <?php if ($errors): ?>
        <div class="message error">
            <?php foreach ($errors as $e): ?>
                <div><?php echo htmlspecialchars((string)$e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success">
            Book updated successfully. <a href="view.php?id=<?php echo (int)$id; ?>">View book</a>
        </div>
    <?php endif; ?>

    <form method="post" action="edit.php?id=<?php echo (int)$id; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">

        <div class="form-row">
            <label>Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars((string)$book['title']); ?>" required>
        </div>

        <div class="form-row">
            <label>Author</label>
            <input type="text" name="author" value="<?php echo htmlspecialchars((string)$book['author']); ?>" required>
        </div>

        <div class="form-row">
            <label>Genre</label>
            <input type="text" name="genre" value="<?php echo htmlspecialchars((string)$book['genre']); ?>" required>
        </div>

        <div class="form-row">
            <label>Year</label>
            <input type="number" name="year" min="1" value="<?php echo htmlspecialchars((string)$book['year']); ?>" required>
        </div>

        <div class="form-row">
            <label>Summary</label>
            <textarea name="summary" rows="5"><?php echo htmlspecialchars((string)($book['summary'] ?? '')); ?></textarea>
        </div>

        <button type="submit">Save Changes</button>
        <a class="link-btn" href="index.php">Back</a>
    </form>
</div>
</body>
</html>