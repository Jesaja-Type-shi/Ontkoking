<?php
require_once __DIR__ . '/../includes/helpers.php';

$terug = safeRedirectTarget($_GET['terug'] ?? $_POST['terug'] ?? null);
if (isLoggedIn()) {
    header('Location: ' . $terug);
    exit;
}

$message = '';
$success = false;
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim(postValue('username'));

    if (!csrfOk($_POST['csrf'] ?? null)) {
        $message = 'Je sessie is verlopen. Probeer het opnieuw.';
    } else {
        $result = loginUser($conn, $oldUsername, postValue('password'));
        $message = $result['message'];
        $success = $result['success'];

        if ($success) {
            header('Location: ' . $terug);
            exit;
        }
    }
}

$paginaTitel = 'Inloggen';
$css  = 'style.css';
$base = '../';
include __DIR__ . '/../includes/header.php';
?>

<main>
    <div class="login-kaart">
        <h1>Inloggen</h1>

        <?php if ($message && !$success): ?>
            <div class="melding"><?= e($message) ?></div>
        <?php endif; ?>

        <form method="post" class="auth-form">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="terug" value="<?= e($terug) ?>">
            <label>Gebruikersnaam of e-mail
                <input type="text" name="username" required autocomplete="username" value="<?= e($oldUsername) ?>">
            </label>
            <label>Wachtwoord
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button class="knop" type="submit">Inloggen</button>
        </form>

        <p class="switch">Nog geen account? <a href="register.php">Registreer je</a></p>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
