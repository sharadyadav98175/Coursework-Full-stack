<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars(SITE_NAME); ?></title>
<link rel="stylesheet" href="<?php echo htmlspecialchars(BASE_URL); ?>assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container">
    <h1><a href="<?php echo htmlspecialchars(BASE_URL); ?>"><?php echo htmlspecialchars(SITE_NAME); ?></a></h1>
    <nav><a href="index.php">Home</a> | <a href="add.php">Add Book</a></nav>
  </div>
</header>
<main class="container">
