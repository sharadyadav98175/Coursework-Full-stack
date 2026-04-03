<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

requireAuth();

$pdo = getPDO();

$params = [];
$where = [];

$title = trim($_GET['title'] ?? '');
$author = trim($_GET['author'] ?? '');
$genre = trim($_GET['genre'] ?? '');
$yearFrom = trim($_GET['year_from'] ?? '');
$yearTo = trim($_GET['year_to'] ?? '');

if ($title !== '') {
    $where[] = 'title LIKE :title';
    $params[':title'] = '%' . $title . '%';
}
if ($author !== '') {
    $where[] = 'author LIKE :author';
    $params[':author'] = '%' . $author . '%';
}
if ($genre !== '') {
    $where[] = 'genre = :genre';
    $params[':genre'] = $genre;
}
if ($yearFrom !== '') {
    $where[] = 'year >= :year_from';
    $params[':year_from'] = (int)$yearFrom;
}
if ($yearTo !== '') {
    $where[] = 'year <= :year_to';
    $params[':year_to'] = (int)$yearTo;
}

$sql = 'SELECT * FROM books';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC, id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$totalBooks = (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$genres = $pdo->query('SELECT DISTINCT genre FROM books ORDER BY genre')->fetchAll(PDO::FETCH_COLUMN);

$token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .ajax-box {
            margin-top: 25px;
            padding: 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #f8f9fa;
        }

        .ajax-results {
            margin-top: 15px;
        }

        .ajax-results table {
            margin-top: 10px;
        }

        .loading {
            color: #0d6efd;
            font-weight: bold;
        }

        .no-result {
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div>
            <h1><?php echo htmlspecialchars(SITE_NAME); ?></h1>
            <div class="small">Welcome, <?php echo htmlspecialchars((string)$_SESSION['username']); ?></div>
        </div>
        <div class="nav-links">
            <a class="btn" href="add.php">Add Book</a>
            <a class="link-btn" href="logout.php">Logout</a>
        </div>
    </div>

    <p><strong>Total Books:</strong> <?php echo $totalBooks; ?></p>

    <form method="get" action="index.php">
        <div class="search-grid">
            <div>
                <label>Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>">
            </div>
            <div>
                <label>Author</label>
                <input type="text" name="author" value="<?php echo htmlspecialchars($author); ?>">
            </div>
            <div>
                <label>Genre</label>
                <select name="genre">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo htmlspecialchars((string)$g); ?>" <?php echo $genre === $g ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars((string)$g); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Year From</label>
                <input type="number" name="year_from" value="<?php echo htmlspecialchars($yearFrom); ?>">
            </div>
            <div>
                <label>Year To</label>
                <input type="number" name="year_to" value="<?php echo htmlspecialchars($yearTo); ?>">
            </div>
        </div>

        <div style="margin-top:14px;">
            <button type="submit">Search</button>
            <a class="link-btn" href="index.php">Clear</a>
        </div>
    </form>

    <table>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Genre</th>
            <th>Year</th>
            <th>Summary</th>
            <th>Actions</th>
        </tr>

        <?php if (!$books): ?>
            <tr>
                <td colspan="6">No books found.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($books as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$b['title']); ?></td>
                    <td><?php echo htmlspecialchars((string)$b['author']); ?></td>
                    <td><?php echo htmlspecialchars((string)$b['genre']); ?></td>
                    <td><?php echo htmlspecialchars((string)$b['year']); ?></td>
                    <td><?php echo htmlspecialchars((string)($b['summary'] ?? '')); ?></td>
                    <td class="actions">
                        <a href="view.php?id=<?php echo (int)$b['id']; ?>">View</a>
                        <a href="edit.php?id=<?php echo (int)$b['id']; ?>">Edit</a>

                        <form class="inline" method="post" action="delete.php" onsubmit="return confirm('Delete this book?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                            <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <div class="ajax-box">
        <h2>AJAX Book Search</h2>

        <div class="form-row">
            <label for="ajaxSearch">Search by Title</label>
            <input type="text" id="ajaxSearch" placeholder="Type a book title...">
        </div>

        <button type="button" id="loadAllBtn">Load All Books with AJAX</button>

        <div id="ajaxStatus" class="ajax-results"></div>
        <div id="ajaxResults" class="ajax-results"></div>
    </div>
</div>

<script>
const ajaxSearch = document.getElementById('ajaxSearch');
const loadAllBtn = document.getElementById('loadAllBtn');
const ajaxStatus = document.getElementById('ajaxStatus');
const ajaxResults = document.getElementById('ajaxResults');

function renderBooks(data) {
    if (!Array.isArray(data) || data.length === 0) {
        ajaxResults.innerHTML = '<p class="no-result">No books found.</p>';
        return;
    }

    let html = `
        <table>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Genre</th>
                <th>Year</th>
                <th>Summary</th>
            </tr>
    `;

    data.forEach(book => {
        html += `
            <tr>
                <td>${escapeHtml(book.title ?? '')}</td>
                <td>${escapeHtml(book.author ?? '')}</td>
                <td>${escapeHtml(book.genre ?? '')}</td>
                <td>${escapeHtml(String(book.year ?? ''))}</td>
                <td>${escapeHtml(book.summary ?? '')}</td>
            </tr>
        `;
    });

    html += '</table>';
    ajaxResults.innerHTML = html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function fetchBooks(search = '') {
    ajaxStatus.innerHTML = '<p class="loading">Loading...</p>';
    ajaxResults.innerHTML = '';

    let url = 'https://mi-linux.wlv.ac.uk/~2413835/ajax/ajax.php';

    if (search.trim() !== '') {
        url += '?search=' + encodeURIComponent(search.trim());
    }

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not OK');
            }
            return response.json();
        })
        .then(data => {
            ajaxStatus.innerHTML = '';
            renderBooks(data);
        })
        .catch(() => {
            ajaxStatus.innerHTML = '<p class="error">Error loading books.</p>';
            ajaxResults.innerHTML = '';
        });
}

let typingTimer;
ajaxSearch.addEventListener('input', () => {
    clearTimeout(typingTimer);

    typingTimer = setTimeout(() => {
        const value = ajaxSearch.value.trim();
        if (value === '') {
            ajaxResults.innerHTML = '';
            ajaxStatus.innerHTML = '';
            return;
        }
        fetchBooks(value);
    }, 400);
});

loadAllBtn.addEventListener('click', () => {
    fetchBooks();
});
</script>
</body>
</html>