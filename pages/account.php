<?php
require_once __DIR__ . '/../includes/helpers.php';

$user = currentUser($conn);
if (!$user) {
    header('Location: login.php?terug=account.php');
    exit;
}

$message = '';
$success = false;
$receptMessage = '';
$receptSuccess = false;

if (($_GET['ok'] ?? '') === 'toegevoegd') {
    $receptMessage = 'Je recept is toegevoegd. Je vindt het nu ook bij Recepten.';
    $receptSuccess = true;
} elseif (($_GET['ok'] ?? '') === 'verwijderd') {
    $receptMessage = 'Het recept is verwijderd.';
    $receptSuccess = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actie = postValue('actie');

    if (!csrfOk($_POST['csrf'] ?? null)) {
        $message = 'Je sessie is verlopen of het bestand is te groot. Probeer het opnieuw.';
    } elseif ($actie === 'wachtwoord') {
        $result = changePassword($conn, (int) $user['id'], postValue('huidig'), postValue('nieuw'));
        $message = $result['message'];
        $success = $result['success'];
    } elseif ($actie === 'verwijder') {
        $result = deleteAccount($conn, (int) $user['id'], postValue('huidig'));
        if ($result['success']) {
            logoutUser();
            header('Location: ../index.php');
            exit;
        }
        $message = $result['message'];
    } elseif ($actie === 'recept_toevoegen') {
        $result = addUserRecipe($conn, (int) $user['id'], $_FILES['foto'] ?? []);
        if ($result['success']) {
            header('Location: account.php?ok=toegevoegd#mijn-recepten');
            exit;
        }
        $receptMessage = $result['message'];
    } elseif ($actie === 'recept_verwijderen') {
        if (deleteUserRecipe($conn, (int) $user['id'], postValue('id'))) {
            header('Location: account.php?ok=verwijderd#mijn-recepten');
            exit;
        }
        $receptMessage = 'Het recept kon niet worden verwijderd.';
    }
}

$aantalFav = count(getFavorites($conn, (int) $user['id']));
$mijnRecepten = getUserRecipes($conn, (int) $user['id']);
$receptFout = $receptMessage !== '' && !$receptSuccess;
$old = fn(string $naam): string => $receptFout ? e(postValue($naam)) : '';

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

        <section class="blok" id="recept-toevoegen">
            <h2>Recept toevoegen</h2>
            <?php if ($receptMessage !== ''): ?>
                <div class="melding <?= $receptSuccess ? 'ok' : '' ?>"><?= e($receptMessage) ?></div>
            <?php endif; ?>
            <form class="auth-form recept-form" method="post" action="account.php#recept-toevoegen" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="actie" value="recept_toevoegen">

                <label>Titel
                    <input type="text" name="titel" required minlength="3" maxlength="60" value="<?= $old('titel') ?>">
                </label>

                <div class="rij">
                    <label>Categorie
                        <select name="cat" required>
                            <?php foreach (RECIPE_CATEGORIES as $naam => $emoji): ?>
                                <option value="<?= e($naam) ?>" <?= $receptFout && postValue('cat') === $naam ? 'selected' : '' ?>><?= e($naam) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Land (optioneel)
                        <select name="land">
                            <option value="">Geen land</option>
                            <?php foreach (RECIPE_COUNTRIES as $naam => $vlag): ?>
                                <option value="<?= e($naam) ?>" <?= $receptFout && postValue('land') === $naam ? 'selected' : '' ?>><?= e($naam) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <label class="check">
                    <input type="checkbox" name="vegetarisch" value="1" <?= $receptFout && postValue('vegetarisch') === '1' ? 'checked' : '' ?>> Vegetarisch
                </label>

                <label>Korte beschrijving (optioneel)
                    <textarea name="intro" rows="2" maxlength="300"><?= $old('intro') ?></textarea>
                </label>

                <div class="rij">
                    <label>Voorbereiding (minuten)
                        <input type="number" name="prep" min="0" max="600" required value="<?= $receptFout ? e(postValue('prep')) : '10' ?>">
                    </label>
                    <label>Koken of bakken (minuten)
                        <input type="number" name="kook" min="0" max="600" required value="<?= $receptFout ? e(postValue('kook')) : '20' ?>">
                    </label>
                </div>

                <div class="rij">
                    <label>Moeilijkheid
                        <select name="niveau">
                            <?php foreach (RECIPE_LEVELS as $niveau): ?>
                                <option <?= $receptFout && postValue('niveau') === $niveau ? 'selected' : '' ?>><?= e($niveau) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Hoeveel?
                        <span class="samen">
                            <input type="number" name="porties" min="1" max="50" required value="<?= $receptFout ? e(postValue('porties')) : '4' ?>">
                            <select name="eenheid">
                                <?php foreach (RECIPE_UNITS as $eenheid): ?>
                                    <option <?= $receptFout && postValue('eenheid') === $eenheid ? 'selected' : '' ?>><?= e($eenheid) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </span>
                    </label>
                </div>

                <label>Ingrediënten
                    <textarea name="ingredienten" rows="6" required placeholder="200 g bloem&#10;2 eieren&#10;1 snufje zout"><?= $old('ingredienten') ?></textarea>
                    <span class="hint">Eén per regel. Begin je met een getal (zoals "200 g bloem"), dan rekent de portiekeuze automatisch mee.</span>
                </label>

                <label>Bereiding
                    <textarea name="stappen" rows="6" required placeholder="Meng de bloem met de eieren.&#10;Bak 5 minuten: Bak de pannenkoeken goudbruin."><?= $old('stappen') ?></textarea>
                    <span class="hint">Eén stap per regel. Wil je een titel bij een stap, schrijf dan "Titel: uitleg".</span>
                </label>

                <label>Foto (optioneel)
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
                    <span class="hint">jpg, png of webp, maximaal 2 MB.</span>
                </label>

                <button class="knop" type="submit">Recept opslaan</button>
            </form>
        </section>

        <?php if ($mijnRecepten): ?>
        <section class="blok" id="mijn-recepten">
            <h2>Mijn recepten</h2>
            <ul class="mijn-recepten">
                <?php foreach ($mijnRecepten as $recept): ?>
                    <li>
                        <a href="detail.php?id=<?= e($recept['id']) ?>"><?= e($recept['titel']) ?></a>
                        <form method="post" action="account.php#mijn-recepten" onsubmit="return confirm('Dit recept verwijderen?')">
                            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="actie" value="recept_verwijderen">
                            <input type="hidden" name="id" value="<?= e($recept['id']) ?>">
                            <button class="knop kleinknop gevaar" type="submit">Verwijderen</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
