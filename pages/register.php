<?php
require_once __DIR__ . '/../includes/helpers.php';

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$message = '';
$success = false;
$oldUsername = '';
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim(postValue('username'));
    $oldEmail = trim(postValue('email'));

    if (!csrfOk($_POST['csrf'] ?? null)) {
        $message = 'Je sessie is verlopen. Probeer het opnieuw.';
    } else {
        $result = registerUser($conn, $oldUsername, $oldEmail, postValue('password'));
        $message = $result['message'];
        $success = $result['success'];
    }
}

$paginaTitel = 'Registreren';
$css  = 'style.css';
$base = '../';
include __DIR__ . '/../includes/header.php';
?>

<main>
    <div class="login-kaart">
        <h1>Account aanmaken</h1>

        <?php if ($message): ?>
            <div class="melding <?= $success ? 'ok' : '' ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" class="auth-form">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <label>Gebruikersnaam
                <input type="text" name="username" required maxlength="20" autocomplete="username" value="<?= e($oldUsername) ?>">
            </label>
            <label>E-mailadres
                <input type="email" name="email" required maxlength="100" autocomplete="email" value="<?= e($oldEmail) ?>">
            </label>
            <label>Wachtwoord (8 tot 72 tekens)
                <input type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">
            </label>
            <button class="knop" type="submit">Registreren</button>
        </form>
        <?php endif; ?>

        <p class="switch">Heb je al een account? <a href="login.php">Log in</a></p>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
