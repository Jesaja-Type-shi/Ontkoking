<?php
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = loginUser(
        $conn,
        $_POST['username'] ?? '',
        $_POST['password'] ?? ''
    );
    $message = $result['message'];
    $success = $result['success'];

    if ($success) {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 360px; }
        h1 { font-size: 1.4rem; margin-bottom: 1rem; }
        label { display: block; margin-top: 1rem; font-size: 0.9rem; font-weight: 600; }
        input { width: 100%; padding: 0.6rem; margin-top: 0.3rem; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; margin-top: 1.5rem; padding: 0.7rem; border: none; border-radius: 8px; background: #e3b9a1; color: #fff; font-weight: 600; cursor: pointer; }
        button:hover { background: #e3b9a1; }
        .message { margin-top: 1rem; padding: 0.6rem; border-radius: 8px; font-size: 0.9rem; }
        .error { background: #e3b9a1; color: #a11e1e; }
        p.switch { text-align: center; margin-top: 1rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Inloggen</h1>

        <?php if ($message && !$success): ?>
            <div class="message error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <label for="username">Gebruikersnaam of e-mail</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Wachtwoord</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Inloggen</button>
        </form>

        <p class="switch">Nog geen account? <a href="register.php">Registreer je</a></p>
    </div>
</body>
</html>
