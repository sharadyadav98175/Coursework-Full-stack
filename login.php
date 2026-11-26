
<?php

require_once __DIR__ . '/auth.php';

function loginFailed($message) {
    $_SESSION['login_error'] = $message;
    header('Location: login-form.php');
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login-form.php');
    exit;
}

// CSRF protection
$token = $_POST['csrf_token'] ?? null;

if (!is_string($token) || !validateCsrfToken($token)) {
    loginFailed('Invalid security token.');
}

// Input validation
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!is_string($username) || !is_string($password)) {
    loginFailed('Invalid input.');
}

$username = trim($username);

if ($username === '' || $password === '') {
    loginFailed('Please fill in all fields.');
}

// Login attempt protection
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if (($_SESSION['lock_until'] ?? 0) > time()) {
    loginFailed('Too many attempts. Try again in 5 minutes.');
}

if (isset($_SESSION['lock_until'])) {
    unset($_SESSION['lock_until']);
    $_SESSION['login_attempts'] = 0;
}

// CAPTCHA validation
$captcha = filter_input(
    INPUT_POST,
    'captcha',
    FILTER_VALIDATE_INT
);

$expectedAnswer = $_SESSION['captcha_answer'] ?? null;
$captchaTime = $_SESSION['captcha_time'] ?? 0;

// Prevent CAPTCHA reuse
unset($_SESSION['captcha_answer']);
unset($_SESSION['captcha_time']);

// CAPTCHA expires after 5 minutes
if (
    $expectedAnswer === null ||
    time() - $captchaTime > 300 ||
    $captcha === false ||
    $captcha === null ||
    $captcha !== $expectedAnswer
) {

    $_SESSION['login_attempts']++;

    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['lock_until'] = time() + 300;
    }

    loginFailed('Incorrect or expired CAPTCHA. Please try again.');
}

// Existing MySQLi database connection
require_once __DIR__ . '/db.php';

try {

    $mysqli->set_charset('utf8mb4');

    // Prepared statement prevents SQL injection
    $stmt = $mysqli->prepare(
        "SELECT user_id, username, password
         FROM users
         WHERE username = ?
         LIMIT 1"
    );

    if (!$stmt) {
        throw new RuntimeException('Query failed.');
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $stmt->bind_result(
        $user_id,
        $db_username,
        $hashed_password
    );

    $found = $stmt->fetch();

    $stmt->close();

    // Verify hashed password
    if (
        $found &&
        password_verify($password, $hashed_password)
    ) {

        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $db_username;
        $_SESSION['login_attempts'] = 0;

        unset($_SESSION['login_error']);
        unset($_SESSION['lock_until']);
        unset($_SESSION['csrf_token']);

        header('Location: index.php');
        exit;
    }

    // Incorrect login attempt
    $_SESSION['login_attempts']++;

    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['lock_until'] = time() + 300;
    }

    loginFailed('Invalid username or password.');

} catch (Throwable $e) {

    error_log($e->getMessage());

    loginFailed('Database error. Please try again.');
}
