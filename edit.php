
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Session protection
requireAuth();

$mysqli->set_charset("utf8mb4");

// Prevent XSS
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Validate book ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: index.php');
    exit;
}

// Retrieve existing book
$stmt = $mysqli->prepare(
    "SELECT title, author, genre, year, summary
     FROM books
     WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$stmt->bind_result(
    $title,
    $author,
    $genre,
    $year,
    $summary
);

if (!$stmt->fetch()) {
    $stmt->close();
    header('Location: index.php');
    exit;
}

$stmt->close();

$errors = [];
$success = false;

// Process update form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? null;

    // CSRF protection
    if (!is_string($token) || !validateCsrfToken($token)) {

        $errors[] = 'Invalid security token.';

    } else {

        // Input filtering
        $title = is_string($_POST['title'] ?? null)
            ? trim($_POST['title']) : '';

        $author = is_string($_POST['author'] ?? null)
            ? trim($_POST['author']) : '';

        $genre = is_string($_POST['genre'] ?? null)
            ? trim($_POST['genre']) : '';

        $year = filter_input(
            INPUT_POST,
            'year',
            FILTER_VALIDATE_INT
        );

        $summary = is_string($_POST['summary'] ?? null)
            ? trim($_POST['summary']) : '';

        // Input validation
        if ($title === '') {
            $errors[] = 'Book title is required.';
        }

        if ($author === '') {
            $errors[] = 'Author is required.';
        }

        if ($genre === '') {
            $errors[] = 'Genre is required.';
        }

        if (!$year || $year < 1 || $year > 9999) {
            $errors[] = 'Please enter a valid year.';
        }

        if (
            strlen($title) > 255 ||
            strlen($author) > 255 ||
            strlen($genre) > 100
        ) {
            $errors[] = 'Input exceeds the allowed length.';
        }

        // Update database
        if (empty($errors)) {

            try {

                $stmt = $mysqli->prepare(
                    "UPDATE books
                     SET title = ?,
                         author = ?,
                         genre = ?,
                         year = ?,
                         summary = ?
                     WHERE id = ?"
                );

                // SQL injection protection
                $stmt->bind_param(
                    "sssisi",
                    $title,
                    $author,
                    $genre,
                    $year,
                    $summary,
                    $id
                );

                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Unable to update book.';
                }

                $stmt->close();

            } catch (Throwable $exception) {

                error_log($exception->getMessage());

                $errors[] = 'Database update failed.';
            }
        }
    }
}

$token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Book</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container" style="max-width:700px;">

    <h1>Edit Book</h1>

    <p>Update the book information below.</p>

    <?php if (!empty($errors)): ?>

        <div class="message error">

            <?php foreach ($errors as $error): ?>

                <p><?= e($error) ?></p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <?php if ($success): ?>

        <div class="message success">

            Book updated successfully!

            <a href="view.php?id=<?= (int)$id ?>">
                View Book
            </a>

        </div>

    <?php endif; ?>

    <form method="post"
          action="edit.php?id=<?= (int)$id ?>">

        <input type="hidden"
               name="csrf_token"
               value="<?= e($token) ?>">

        <div class="form-row">

            <label for="title">Title</label>

            <input type="text"
                   id="title"
                   name="title"
                   maxlength="255"
                   value="<?= e($title) ?>"
                   required>

        </div>

        <div class="form-row">

            <label for="author">Author</label>

            <input type="text"
                   id="author"
                   name="author"
                   maxlength="255"
                   value="<?= e($author) ?>"
                   required>

        </div>

        <div class="form-row">

            <label for="genre">Genre</label>

            <input type="text"
                   id="genre"
                   name="genre"
                   maxlength="100"
                   value="<?= e($genre) ?>"
                   required>

        </div>

        <div class="form-row">

            <label for="year">Publication Year</label>

            <input type="number"
                   id="year"
                   name="year"
                   min="1"
                   max="9999"
                   value="<?= e($year) ?>"
                   required>

        </div>

        <div class="form-row">

            <label for="summary">Summary</label>

            <textarea id="summary"
                      name="summary"
                      rows="5"><?= e($summary ?? '') ?></textarea>

        </div>

        <button type="submit">
            Save Changes
        </button>

        <a class="link-btn" href="index.php">
            Back to Library
        </a>

    </form>

</div>

</body>
</html>
