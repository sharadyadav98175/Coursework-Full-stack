<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Library System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width:420px;">
    <h2>Login</h2>

    <?php if (!empty($_SESSION['login_error'])): ?>
        <div class="message error"><?php echo htmlspecialchars((string)$_SESSION['login_error']); ?></div>
        <?php unset($_SESSION['login_error']); ?>
    <?php endif; ?>

    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">

        <div class="form-row">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-row">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit">Login</button>
    </form>

    <p class="small" style="margin-top:12px;">Use the user you insert into the database.</p>
</div>
</body>
</html>