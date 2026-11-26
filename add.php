
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

$errors = [];
$success = false;

$title = '';
$author = '';
$genre = '';
$year = '';
$summary = '';

// Process add book form
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

        // Insert book into database
        if (empty($errors)) {

            try {

                $stmt = $mysqli->prepare(
                    "INSERT INTO books
                    (title, author, genre, year, summary)
                    VALUES (?, ?, ?, ?, ?)"
                );

                // SQL injection protection
                $stmt->bind_param(
                    "sssis",
                    $title,
                    $author,
                    $genre,
                    $year,
                    $summary
                );

                if ($stmt->execute()) {

                    $success = true;

                    // Clear form after successful insertion
                    $title = '';
                    $author = '';
                    $genre = '';
                    $year = '';
                    $summary = '';

                } else {

                    $errors[] = 'Unable to add book.';

                }

                $stmt->close();

            } catch (Throwable $exception) {

                error_log($exception->getMessage());

                $errors[] = 'Database insertion failed.';

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

    <title>Add Book</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<div class="container" style="max-width:700px;">

    <h1>Add New Book</h1>

    <p>Enter the book information below.</p>

    <?php if (!empty($errors)): ?>

        <div class="message error">

            <?php foreach ($errors as $error): ?>

                <p><?= e($error) ?></p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <?php if ($success): ?>

        <div class="message success">

            Book added successfully!

            <a href="index.php">
                View Library
            </a>

        </div>

    <?php endif; ?>

    <form method="post" action="add.php">

        <input type="hidden"
               name="csrf_token"
               value="<?= e($token) ?>">

        <div class="form-row">

            <label for="title">Book Title</label>

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
                      rows="5"><?= e($summary) ?></textarea>

        </div>

        <button type="submit">
            Add Book
        </button>

        <a class="link-btn" href="index.php">
            Back to Library
        </a>

    </form>

</div>

</body>
</html>
