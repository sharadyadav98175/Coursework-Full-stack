
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

requireAuth();

$mysqli->set_charset("utf8mb4");

// Escape output to prevent XSS
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Search inputs
$title = trim($_GET['title'] ?? '');
$author = trim($_GET['author'] ?? '');
$genre = trim($_GET['genre'] ?? '');
$yearFrom = trim($_GET['year_from'] ?? '');
$yearTo = trim($_GET['year_to'] ?? '');

$titleSearch = '%' . $title . '%';
$authorSearch = '%' . $author . '%';

$from = $yearFrom === '' ? 0 : (int)$yearFrom;
$to = $yearTo === '' ? 9999 : (int)$yearTo;

// Multiple criteria search
$sql = "SELECT * FROM books
        WHERE title LIKE ?
        AND author LIKE ?
        AND (? = '' OR genre = ?)
        AND year BETWEEN ? AND ?
        ORDER BY id DESC";

$stmt = $mysqli->prepare($sql);

$stmt->bind_param(
    "ssssii",
    $titleSearch,
    $authorSearch,
    $genre,
    $genre,
    $from,
    $to
);

$stmt->execute();
$books = $stmt->get_result();

// Count total books
$countResult = $mysqli->query(
    "SELECT COUNT(*) AS total FROM books"
);

$totalBooks = $countResult->fetch_assoc()['total'];

// Get available genres
$genres = $mysqli->query(
    "SELECT DISTINCT genre FROM books ORDER BY genre"
);

// Generate CSRF token
$token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Student Library System</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <div class="topbar">

        <div>
            <h1>Student Library System</h1>

            <p>
                Welcome,
                <?= e($_SESSION['username'] ?? '') ?>
            </p>
        </div>

        <div class="nav-links">

            <a class="btn" href="add.php">
                Add Book
            </a>

            <a class="link-btn" href="logout.php">
                Logout
            </a>

        </div>

    </div>

    <p>
        <strong>Total Books:</strong>
        <?= e($totalBooks) ?>
    </p>

    <h2>Search Books</h2>

    <form method="get" action="index.php">

        <div class="search-grid">

            <div>
                <label>Title</label>
                <input type="text"
                       name="title"
                       value="<?= e($title) ?>">
            </div>

            <div>
                <label>Author</label>
                <input type="text"
                       name="author"
                       value="<?= e($author) ?>">
            </div>

            <div>
                <label>Genre</label>

                <select name="genre">

                    <option value="">
                        All Genres
                    </option>

                    <?php while ($g = $genres->fetch_assoc()): ?>

                        <option
                            value="<?= e($g['genre']) ?>"
                            <?= $genre === $g['genre']
                                ? 'selected' : '' ?>>

                            <?= e($g['genre']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>
            </div>

            <div>
                <label>Year From</label>

                <input type="number"
                       name="year_from"
                       min="1"
                       max="9999"
                       value="<?= e($yearFrom) ?>">
            </div>

            <div>
                <label>Year To</label>

                <input type="number"
                       name="year_to"
                       min="1"
                       max="9999"
                       value="<?= e($yearTo) ?>">
            </div>

        </div>

        <div style="margin-top: 15px;">

            <button type="submit">
                Search
            </button>

            <a class="link-btn" href="index.php">
                Clear
            </a>

        </div>

    </form>

    <div style="overflow-x: auto;">

        <table>

            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Genre</th>
                    <th>Year</th>
                    <th>Summary</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($books->num_rows > 0): ?>

                <?php while ($book = $books->fetch_assoc()): ?>

                    <tr>

                        <td><?= e($book['title']) ?></td>

                        <td><?= e($book['author']) ?></td>

                        <td><?= e($book['genre']) ?></td>

                        <td><?= e($book['year']) ?></td>

                        <td><?= e($book['summary'] ?? '') ?></td>

                        <td class="actions">

                            <a href="view.php?id=<?= (int)$book['id'] ?>">
                                View
                            </a>

                            <a href="edit.php?id=<?= (int)$book['id'] ?>">
                                Edit
                            </a>

                            <form method="post"
                                  action="delete.php"
                                  class="inline"
                                  onsubmit="return confirm('Delete this book?');">

                                <input type="hidden"
                                       name="id"
                                       value="<?= (int)$book['id'] ?>">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= e($token) ?>">

                                <button type="submit"
                                        class="btn-danger">
                                    Delete
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6">
                        No books found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

    <hr style="margin: 30px 0;">

    <h2>AJAX Book Search</h2>

    <div class="form-row">

        <label for="ajaxSearch">
            Search by Book Title
        </label>

        <input type="text"
               id="ajaxSearch"
               placeholder="Type a book title...">

    </div>

    <button type="button" id="loadAllBtn">
        Load All Books with AJAX
    </button>

    <p id="ajaxStatus"></p>

    <div id="ajaxResults"></div>

</div>

<script>

const searchInput = document.getElementById('ajaxSearch');
const results = document.getElementById('ajaxResults');
const status = document.getElementById('ajaxStatus');

let timer;
let controller;

async function loadBooks(search = '') {

    if (controller) {
        controller.abort();
    }

    controller = new AbortController();

    status.textContent = 'Loading...';
    results.replaceChildren();

    try {

        const response = await fetch(
            'ajax.php?search=' + encodeURIComponent(search),
            { signal: controller.signal }
        );

        if (!response.ok) {
            throw new Error('Request failed');
        }

        const books = await response.json();

        status.textContent = '';

        if (!Array.isArray(books) || books.length === 0) {

            status.textContent = 'No books found.';
            return;

        }

        const table = document.createElement('table');
        const header = table.insertRow();

        ['Title', 'Author', 'Genre', 'Year', 'Summary']
            .forEach(function(label) {

                const th = document.createElement('th');
                th.textContent = label;
                header.appendChild(th);

            });

        books.forEach(function(book) {

            const row = table.insertRow();

            ['title', 'author', 'genre', 'year', 'summary']
                .forEach(function(field) {

                    const cell = row.insertCell();
                    cell.textContent = book[field] ?? '';

                });

        });

        results.appendChild(table);

    } catch (error) {

        if (error.name !== 'AbortError') {
            status.textContent = 'Error loading books.';
        }

    }

}

searchInput.addEventListener('input', function() {

    clearTimeout(timer);

    timer = setTimeout(function() {
        loadBooks(searchInput.value.trim());
    }, 400);

});

document.getElementById('loadAllBtn')
    .addEventListener('click', function() {

        clearTimeout(timer);
        loadBooks();

    });

</script>

</body>
</html>
<?php $stmt->close(); ?>
