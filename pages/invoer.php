<?php
require_once __DIR__ . '/../includes/auth.php';

// Alleen ingelogde gebruikers mogen recepten toevoegen
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/recipes.php';

$fouten  = [];
$bewerk  = null;   // het recept dat wordt aangepast (of null bij nieuw recept)
$waarden = ['naam' => '', 'categorie' => '', 'tijd' => '', 'ingredienten' => '', 'bereiding' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        // Upload groter dan post_max_size: PHP gooit dan alle formuliervelden weg
        $fouten[] = 'De foto is te groot (maximaal 5 MB).';
    } elseif (!checkCsrf()) {
        $fouten[] = 'Ongeldige aanvraag. Probeer het opnieuw.';
    } else {
        $actie = $_POST['actie'] ?? '';
        $id    = (int)($_POST['id'] ?? 0);

        if ($actie === 'verwijderen') {
            $recept = getRecipe($conn, $id);
            if ($recept && canEditRecipe($recept)) {
                deleteRecipe($conn, $id);   // verwijdert ook de foto
                $_SESSION['melding'] = 'Recept verwijderd!';
            } else {
                $_SESSION['melding'] = 'Je kunt dit recept niet verwijderen.';
            }
            header('Location: invoer.php');
            exit;
        }

        if ($actie === 'opslaan') {
            [$fouten, $waarden] = validateRecipe($_POST);

            if ($id > 0) {
                $bewerk = getRecipe($conn, $id);
                if (!$bewerk || !canEditRecipe($bewerk)) {
                    $fouten[] = 'Je kunt dit recept niet aanpassen.';
                    $bewerk = null;
                }
            }

            // Foto pas verwerken als de rest van het formulier klopt
            $nieuweFoto = null;
            if (!$fouten) {
                [$fotoFout, $nieuweFoto] = saveRecipePhoto($_FILES['foto'] ?? []);
                if ($fotoFout) {
                    $fouten[] = $fotoFout;
                }
            }

            if (!$fouten) {
                if ($bewerk) {
                    if ($nieuweFoto) {
                        $foto = $nieuweFoto;                       // nieuwe foto vervangt de oude
                    } elseif (!empty($_POST['foto_verwijderen'])) {
                        $foto = null;                              // foto weghalen
                    } else {
                        $foto = false;                             // foto laten staan
                    }

                    updateRecipe($conn, $id, $waarden, $foto);
                    if ($foto !== false) {
                        deleteRecipePhoto($bewerk['foto'] ?? null);
                    }
                    $_SESSION['melding'] = 'Recept aangepast!';
                } else {
                    addRecipe($conn, (int)$_SESSION['user_id'], $waarden, $nieuweFoto);
                    $_SESSION['melding'] = 'Recept toegevoegd!';
                }
                header('Location: invoer.php');
                exit;
            }
        }
    }
} elseif (isset($_GET['bewerk'])) {
    $recept = getRecipe($conn, (int)$_GET['bewerk']);
    if ($recept && canEditRecipe($recept)) {
        $bewerk  = $recept;
        $waarden = [
            'naam'         => $recept['naam'],
            'categorie'    => $recept['categorie'],
            'tijd'         => $recept['tijd'],
            'ingredienten' => $recept['ingredienten'],
            'bereiding'    => $recept['bereiding'],
        ];
    }
}

// Melding uit de vorige aanvraag (na opslaan/verwijderen)
$melding = $_SESSION['melding'] ?? '';
unset($_SESSION['melding']);

$mijnRecepten = getRecipesByUser($conn, (int)$_SESSION['user_id']);

$paginaTitel = 'Recepten toevoegen';
$css  = 'invoer.css';
$base = '../';
include __DIR__ . '/../includes/header.php';
?>

<main>`
    <link rel="stylesheet" href="../pages/invoer.css">
    <section class="intro">
        <h2>Voeg jouw recept toe</h2>
        <p>Deel je favoriete gerechten</p>
    </section>

    <section class="formulier">
        <h2><?= $bewerk ? 'Recept aanpassen' : 'Nieuw recept' ?></h2>

        <?php foreach ($fouten as $fout): ?>
            <p class="fout"><?= htmlspecialchars($fout) ?></p>
        <?php endforeach; ?>

        <form method="post" action="invoer.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="actie" value="opslaan">
            <input type="hidden" name="id" value="<?= $bewerk ? (int)$bewerk['id'] : 0 ?>">

            <label for="naam">Naam van het recept</label>
            <input type="text" id="naam" name="naam" maxlength="100" required
                   value="<?= htmlspecialchars($waarden['naam']) ?>">

            <label for="categorie">Categorie</label>
            <select id="categorie" name="categorie" required>
                <option value="">Kies een categorie</option>
                <?php foreach (RECEPT_CATEGORIEEN as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $waarden['categorie'] === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="tijd">Bereidingstijd in minuten</label>
            <input type="number" id="tijd" name="tijd" min="1" max="1440" required
                   value="<?= htmlspecialchars((string)$waarden['tijd']) ?>">

            <label for="foto">Foto (optioneel, JPG/PNG/WEBP/GIF, max. 5 MB)</label>
            <?php if ($bewerk && !empty($bewerk['foto'])): ?>
                <img class="huidige-foto" src="<?= $base ?>assets/uploads/<?= htmlspecialchars($bewerk['foto']) ?>" alt="Huidige foto">
                <label class="checkbox-label">
                    <input type="checkbox" name="foto_verwijderen" value="1"> Foto verwijderen
                </label>
                <p class="hint">Kies hieronder een nieuwe foto om de huidige te vervangen.</p>
            <?php endif; ?>
            <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif">

            <label for="ingredienten">Ingrediënten</label>
            <textarea id="ingredienten" name="ingredienten"
                placeholder="Bijvoorbeeld:&#10;200 gram pasta&#10;2 eieren"
                required><?= htmlspecialchars($waarden['ingredienten']) ?></textarea>

            <label for="bereiding">Bereidingswijze</label>
            <textarea id="bereiding" name="bereiding"
                placeholder="Beschrijf de stappen van je recept..."
                required><?= htmlspecialchars($waarden['bereiding']) ?></textarea>

            <button type="submit" id="opslaan"><?= $bewerk ? 'Wijzigingen opslaan' : 'Recept toevoegen' ?></button>
            <?php if ($bewerk): ?>
                <a class="annuleren" href="invoer.php">Annuleren</a>
            <?php endif; ?>
        </form>

        <?php if ($melding): ?>
            <p id="melding"><?= htmlspecialchars($melding) ?></p>
        <?php endif; ?>
    </section>

    <section class="overzicht">
        <h2>Mijn recepten</h2>

        <?php if (!$mijnRecepten): ?>
            <p>Je hebt nog geen recepten toegevoegd.</p>
        <?php endif; ?>

        <?php foreach ($mijnRecepten as $r): ?>
            <div class="recept">
                <?php if (!empty($r['foto'])): ?>
                    <img class="foto" src="<?= $base ?>assets/uploads/<?= htmlspecialchars($r['foto']) ?>" alt="<?= htmlspecialchars($r['naam']) ?>">
                <?php endif; ?>

                <h3><?= htmlspecialchars($r['naam']) ?></h3>
                <p><?= htmlspecialchars($r['categorie']) ?> - <?= (int)$r['tijd'] ?> minuten</p>

                <h4>Ingrediënten</h4>
                <p><?= htmlspecialchars($r['ingredienten']) ?></p>

                <h4>Bereidingswijze</h4>
                <p><?= htmlspecialchars($r['bereiding']) ?></p>

                <form method="get" action="invoer.php" class="inline-form">
                    <input type="hidden" name="bewerk" value="<?= (int)$r['id'] ?>">
                    <button type="submit">Aanpassen</button>
                </form>

                <form method="post" action="invoer.php" class="inline-form"
                      onsubmit="return confirm('Weet je zeker dat je dit recept wilt verwijderen?');">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                    <input type="hidden" name="actie" value="verwijderen">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" class="verwijderen">Verwijderen</button>
                </form>
            </div>
        <?php endforeach; ?>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>