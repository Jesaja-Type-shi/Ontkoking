<?php
// includes/recipes.php
// Alle logica voor recepten (opslaan, ophalen, aanpassen, verwijderen).
// Gebruikt $conn uit dbconfig.php via auth.php.

require_once __DIR__ . '/auth.php';

$conn->exec("CREATE TABLE IF NOT EXISTS recipes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    naam TEXT NOT NULL,
    categorie TEXT NOT NULL,
    tijd INTEGER NOT NULL,
    ingredienten TEXT NOT NULL,
    bereiding TEXT NOT NULL,
    foto TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES login(id)
)");

// Bestaande database: foto-kolom toevoegen als die er nog niet is
$kolommen = array_column($conn->query("PRAGMA table_info(recipes)")->fetchAll(PDO::FETCH_ASSOC), 'name');
if (!in_array('foto', $kolommen, true)) {
    $conn->exec("ALTER TABLE recipes ADD COLUMN foto TEXT");
}

const RECEPT_CATEGORIEEN = ['Ontbijt', 'Lunch', 'Diner', 'Snacks', 'Dessert'];
const RECEPT_UPLOADMAP   = __DIR__ . '/../assets/uploads';
const RECEPT_FOTO_MAX    = 5 * 1024 * 1024; // 5 MB

/**
 * Controleert en schoont de invoer van het receptformulier op.
 * Geeft [fouten[], data[]] terug.
 */
function validateRecipe(array $input): array
{
    $data = [
        'naam'         => trim($input['naam'] ?? ''),
        'categorie'    => trim($input['categorie'] ?? ''),
        'tijd'         => trim((string)($input['tijd'] ?? '')),
        'ingredienten' => trim($input['ingredienten'] ?? ''),
        'bereiding'    => trim($input['bereiding'] ?? ''),
    ];
    $fouten = [];

    if ($data['naam'] === '' || strlen($data['naam']) > 100) {
        $fouten[] = 'Vul een naam in (maximaal 100 tekens).';
    }
    if (!in_array($data['categorie'], RECEPT_CATEGORIEEN, true)) {
        $fouten[] = 'Kies een categorie.';
    }
    if (!ctype_digit($data['tijd']) || (int)$data['tijd'] < 1 || (int)$data['tijd'] > 1440) {
        $fouten[] = 'Vul een bereidingstijd in minuten in (1 t/m 1440).';
    }
    if ($data['ingredienten'] === '' || strlen($data['ingredienten']) > 5000) {
        $fouten[] = 'Vul de ingrediënten in.';
    }
    if ($data['bereiding'] === '' || strlen($data['bereiding']) > 10000) {
        $fouten[] = 'Vul de bereidingswijze in.';
    }

    return [$fouten, $data];
}

function addRecipe(PDO $conn, int $userId, array $data, ?string $foto = null): void
{
    $stmt = $conn->prepare("INSERT INTO recipes (user_id, naam, categorie, tijd, ingredienten, bereiding, foto)
                            VALUES (:user_id, :naam, :categorie, :tijd, :ingredienten, :bereiding, :foto)");
    $stmt->execute([
        'user_id'      => $userId,
        'naam'         => $data['naam'],
        'categorie'    => $data['categorie'],
        'tijd'         => (int)$data['tijd'],
        'ingredienten' => $data['ingredienten'],
        'bereiding'    => $data['bereiding'],
        'foto'         => $foto,
    ]);
}

/**
 * Past een recept aan.
 * $foto: false = foto niet aanraken, null = foto verwijderen, string = nieuwe foto.
 */
function updateRecipe(PDO $conn, int $id, array $data, $foto = false): void
{
    $sql = "UPDATE recipes SET naam = :naam, categorie = :categorie, tijd = :tijd,
            ingredienten = :ingredienten, bereiding = :bereiding";
    $params = [
        'id'           => $id,
        'naam'         => $data['naam'],
        'categorie'    => $data['categorie'],
        'tijd'         => (int)$data['tijd'],
        'ingredienten' => $data['ingredienten'],
        'bereiding'    => $data['bereiding'],
    ];
    if ($foto !== false) {
        $sql .= ", foto = :foto";
        $params['foto'] = $foto;
    }
    $sql .= " WHERE id = :id";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
}

function deleteRecipe(PDO $conn, int $id): void
{
    $recept = getRecipe($conn, $id);

    $stmt = $conn->prepare("DELETE FROM recipes WHERE id = :id");
    $stmt->execute(['id' => $id]);

    if ($recept) {
        deleteRecipePhoto($recept['foto'] ?? null);
    }
}

/** Eén recept (met gebruikersnaam van de maker), of null. */
function getRecipe(PDO $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT r.*, l.username FROM recipes r
                            LEFT JOIN login l ON l.id = r.user_id WHERE r.id = :id");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/** Alle recepten, nieuwste eerst; optioneel gefilterd op titel en categorie. */
function getRecipes(PDO $conn, string $zoek = '', string $categorie = ''): array
{
    $sql = "SELECT r.*, l.username FROM recipes r LEFT JOIN login l ON l.id = r.user_id WHERE 1=1";
    $params = [];
    if ($zoek !== '') {
        $sql .= " AND r.naam LIKE :zoek";
        $params['zoek'] = '%' . $zoek . '%';
    }
    if ($categorie !== '') {
        $sql .= " AND r.categorie = :categorie";
        $params['categorie'] = $categorie;
    }
    $sql .= " ORDER BY r.created_at DESC, r.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecipesByUser(PDO $conn, int $userId): array
{
    $stmt = $conn->prepare("SELECT * FROM recipes WHERE user_id = :user_id ORDER BY created_at DESC, id DESC");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Mag de ingelogde gebruiker dit recept aanpassen of verwijderen? (eigenaar of admin) */
function canEditRecipe(array $recipe): bool
{
    return isLoggedIn() && ((int)$recipe['user_id'] === (int)$_SESSION['user_id'] || isAdmin());
}

// Eenvoudige CSRF-beveiliging voor de formulieren
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function checkCsrf(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/**
 * Verwerkt een geüploade foto ($_FILES['foto']).
 * Geeft [foutmelding|null, bestandsnaam|null] terug. Geen bestand gekozen = [null, null].
 */
function saveRecipePhoto(array $file): array
{
    $fout = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($fout === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($fout === UPLOAD_ERR_INI_SIZE || $fout === UPLOAD_ERR_FORM_SIZE) {
        return ['De foto is te groot (maximaal 5 MB).', null];
    }
    if ($fout !== UPLOAD_ERR_OK) {
        return ['De foto kon niet worden geüpload. Probeer het opnieuw.', null];
    }
    if ($file['size'] > RECEPT_FOTO_MAX) {
        return ['De foto is te groot (maximaal 5 MB).', null];
    }

    // Controleer aan de inhoud (niet aan de bestandsnaam) of het echt een afbeelding is
    $toegestaan = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $info = @getimagesize($file['tmp_name']);
    $mime = $info['mime'] ?? '';
    if (!isset($toegestaan[$mime])) {
        return ['Alleen JPG, PNG, WEBP of GIF is toegestaan.', null];
    }

    if (!is_dir(RECEPT_UPLOADMAP) && !mkdir(RECEPT_UPLOADMAP, 0755, true)) {
        return ['De uploadmap kon niet worden aangemaakt.', null];
    }

    // Willekeurige bestandsnaam, zodat niemand bestanden kan overschrijven
    $naam = bin2hex(random_bytes(16)) . '.' . $toegestaan[$mime];
    if (!move_uploaded_file($file['tmp_name'], RECEPT_UPLOADMAP . '/' . $naam)) {
        return ['De foto kon niet worden opgeslagen.', null];
    }

    return [null, $naam];
}

function deleteRecipePhoto(?string $foto): void
{
    if ($foto && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|gif)$/', $foto)) {
        $pad = RECEPT_UPLOADMAP . '/' . $foto;
        if (is_file($pad)) {
            @unlink($pad);
        }
    }
}