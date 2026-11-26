
<?php
require_once __DIR__ . '/auth.php';

$token = generateCsrfToken();

// Generate random CAPTCHA
$num1 = random_int(1, 20);
$num2 = random_int(1, 20);

$_SESSION['captcha_answer'] = $num1 + $num2;
$_SESSION['captcha_time'] = time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login - Student Library System</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container" style="max-width:420px;">

    <h2>Student Library System</h2>

    <p>Please log in to continue.</p>

    <?php if (!empty($_SESSION['login_error'])): ?>

        <div class="message error">
            <?= htmlspecialchars(
                $_SESSION['login_error'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

        <?php unset($_SESSION['login_error']); ?>

    <?php endif; ?>

    <form method="post" action="login.php">

        <input type="hidden"
               name="csrf_token"
               value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-row">

            <label for="username">Username</label>

            <input type="text"
                   id="username"
                   name="username"
                   autocomplete="username"
                   required>

        </div>

        <div class="form-row">

            <label for="password">Password</label>

            <input type="password"
                   id="password"
                   name="password"
                   autocomplete="current-password"
                   required>

        </div>

        <div class="form-row">

            <label>CAPTCHA Security Verification</label>

            <div style="
                background:#f4f6f9;
                border:1px solid #ccc;
                padding:15px;
                text-align:center;
                font-size:24px;
                font-weight:bold;
                border-radius:5px;
                margin-bottom:10px;
            ">
                <?= $num1 ?> + <?= $num2 ?> = ?
            </div>

            <label for="captcha">Enter your answer</label>

            <input type="number"
                   id="captcha"
                   name="captcha"
                   placeholder="Enter CAPTCHA answer"
                   autocomplete="off"
                   required>

        </div>

        <button type="submit" style="width:100%;">
            Login
        </button>

    </form>

    <p class="small">
        Protected by CAPTCHA security verification.
    </p>

</div>

</body>
</html>
