<?php
require_once __DIR__ . '/../includes/helpers.php';

$user = currentUser($conn);
if (!$user) {
    header('Location: login.php?terug=account.php');
    exit;
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfOk($_POST['csrf'] ?? null)) {
        $message = 'Je sessie is verlopen. Probeer het opnieuw.';
    } elseif (postValue('actie') === 'wachtwoord') {
        $result = changePassword($conn, (int) $user['id'], postValue('huidig'), postValue('nieuw'));
        $message = $result['message'];
        $success = $result['success'];
    } elseif (postValue('actie') === 'verwijder') {
        $result = deleteAccount($conn, (int) $user['id'], postValue('huidig'));
        if ($result['success']) {
            logoutUser();
            header('Location: ../index.php');
            exit;
        }
        $message = $result['message'];
    }
}

$aantalFav = count(getFavorites($conn, (int) $user['id']));

$paginaTitel = 'Mijn account';
$css  = 'style.css';
$base = '../';
include __DIR__ . '/../includes/header.php';
?>

<main>
    <div class="titelrij"><h1>Mijn account</h1><p>Hallo <?= e($user['username']) ?>!</p></div>

    <div class="account-raster">
        <?php if ($message !== ''): ?>
            <div class="melding <?= $success ? 'ok' : '' ?>"><?= e($message) ?></div>
        <?php endif; ?>

        <section class="blok">
            <h2>Mijn gegevens</h2>
            <dl class="gegevens">
                <dt>Gebruikersnaam</dt><dd><?= e($user['username']) ?></dd>
                <dt>E-mail</dt><dd><?= e($user['email']) ?></dd>
                <dt>Lid sinds</dt><dd><?= e(date('d-m-Y', strtotime($user['created_at'] . ' UTC'))) ?></dd>
                <dt>Favorieten</dt><dd><a href="bibliotheek.php"><?= $aantalFav ?> <?= $aantalFav === 1 ? 'recept' : 'recepten' ?></a></dd>
            </dl>
        </section>

        <section class="blok">
            <h2>Wachtwoord wijzigen</h2>
            <form class="auth-form" method="post" action="account.php">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="actie" value="wachtwoord">
                <label>Huidig wachtwoord<input type="password" name="huidig" required autocomplete="current-password"></label>
                <label>Nieuw wachtwoord (8 tot 72 tekens)<input type="password" name="nieuw" required minlength="8" maxlength="72" autocomplete="new-password"></label>
                <button class="knop" type="submit">Wachtwoord opslaan</button>
            </form>
        </section>

        <section class="blok">
            <h2>Account verwijderen</h2>
            <p class="grijs">Hiermee verwijder je je account, je bibliotheek en je reacties. Dit kan niet ongedaan worden gemaakt.</p>
            <form class="auth-form" method="post" action="account.php" onsubmit="return confirm('Weet je het zeker? Dit kan niet ongedaan worden gemaakt.')">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="actie" value="verwijder">
                <label>Typ je wachtwoord ter bevestiging<input type="password" name="huidig" required autocomplete="current-password"></label>
                <button class="knop gevaar" type="submit">Account verwijderen</button>
            </form>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
