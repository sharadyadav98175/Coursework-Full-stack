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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Book</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width:800px;">
    <h2><?php echo htmlspecialchars((string)$book['title']); ?></h2>

    <p><strong>Author:</strong> <?php echo htmlspecialchars((string)$book['author']); ?></p>
    <p><strong>Genre:</strong> <?php echo htmlspecialchars((string)$book['genre']); ?></p>
    <p><strong>Year:</strong> <?php echo htmlspecialchars((string)$book['year']); ?></p>
    <p><strong>Summary:</strong><br><?php echo nl2br(htmlspecialchars((string)($book['summary'] ?? ''))); ?></p>

    <p>
        <a href="edit.php?id=<?php echo (int)$book['id']; ?>">Edit</a>
        <a href="index.php">Back</a>
    </p>
</div>
</body>
</html>