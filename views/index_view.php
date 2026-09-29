<?php
require_once __DIR__ . '/../auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
</head>
<body>
    <nav>
        <?php if (isLoggedIn()): ?>
            <span>Welkom, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="logout.php">Uitloggen</a>
        <?php else: ?>
            <a href="login.php">Inloggen</a>
        <?php endif; ?>
    </nav>

    <h1>Home</h1>
    <p>Welcome to the home page!</p>
</body>
</html>