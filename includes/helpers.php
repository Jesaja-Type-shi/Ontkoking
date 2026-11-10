<?php

require_once __DIR__ . '/auth.php';

function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function jsonForHtml($data): string
{
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}

function postValue(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? $value : '';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfOk($token): bool
{
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function isValidRecipeId(string $id): bool
{
    return preg_match('/^[a-z0-9-]{1,60}$/', $id) === 1;
}

function safeRedirectTarget($target): string
{
    if (is_string($target) && preg_match('/^[\w-]+\.php(\?[\w=&%.-]*)?$/', $target) === 1) {
        return strpos($target, 'index.php') === 0 ? '../' . $target : $target;
    }
    return '../index.php';
}

function currentUser(PDO $conn): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM login WHERE id = :id");
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $row = $stmt->fetch();
    if (!$row) {
        logoutUser();
        return null;
    }
    return $row;
}

function getFavorites(PDO $conn, int $userId): array
{
    $stmt = $conn->prepare("SELECT recipe_id FROM favorites WHERE user_id = :id ORDER BY rowid");
    $stmt->execute(['id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getComments(PDO $conn, string $recipeId): array
{
    $stmt = $conn->prepare("SELECT l.username AS naam, c.body AS tekst, c.created_at AS datum
                            FROM comments c JOIN login l ON l.id = c.user_id
                            WHERE c.recipe_id = :recept ORDER BY c.id");
    $stmt->execute(['recept' => $recipeId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['datum'] = date('d-m-Y', strtotime($row['datum'] . ' UTC'));
    }
    unset($row);
    return $rows;
}

function apiStop(int $code, string $message): void
{
    http_response_code($code);
    echo json_encode(['fout' => $message]);
    exit;
}

function apiStart(PDO $conn): array
{
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        apiStop(405, 'Alleen POST is toegestaan.');
    }
    if (!csrfOk($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        apiStop(403, 'Ongeldige sessie. Ververs de pagina.');
    }
    $user = currentUser($conn);
    if (!$user) {
        apiStop(401, 'Je moet ingelogd zijn.');
    }
    $input = json_decode(file_get_contents('php://input') ?: '', true);
    return [$user, is_array($input) ? $input : []];
}

const RECIPE_CATEGORIES = ['Ontbijt' => '🥞', 'Lunch' => '🥪', 'Diner' => '🍝', 'Soep' => '🍲',
                           'Salade' => '🥗', 'Snacks' => '🍿', 'Dessert' => '🍰', 'Drankjes' => '🥤'];
const RECIPE_COUNTRIES  = ['België' => '🇧🇪', 'Frankrijk' => '🇫🇷', 'Griekenland' => '🇬🇷', 'Hawaï' => '🌺',
                           'India' => '🇮🇳', 'Indonesië' => '🇮🇩', 'Italië' => '🇮🇹', 'Japan' => '🇯🇵',
                           'Libanon' => '🇱🇧', 'Marokko' => '🇲🇦', 'Mexico' => '🇲🇽', 'Nederland' => '🇳🇱',
                           'Spanje' => '🇪🇸', 'Thailand' => '🇹🇭', 'Turkije' => '🇹🇷', 'Verenigde Staten' => '🇺🇸'];
const RECIPE_LEVELS = ['Makkelijk', 'Gemiddeld', 'Moeilijk'];
const RECIPE_UNITS  = ['personen', 'porties', 'stuks'];

function textLength(string $text): int
{
    return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
}

function getUserRecipes(PDO $conn, ?int $userId = null): array
{
    if ($userId === null) {
        $stmt = $conn->query("SELECT data FROM user_recipes ORDER BY rowid");
    } else {
        $stmt = $conn->prepare("SELECT data FROM user_recipes WHERE user_id = :id ORDER BY rowid");
        $stmt->execute(['id' => $userId]);
    }
    $recipes = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $json) {
        $recipe = json_decode($json, true);
        if (is_array($recipe)) {
            $recipes[] = $recipe;
        }
    }
    return $recipes;
}

function deleteRecipePhotos(string $id): void
{
    foreach (['jpg', 'png', 'webp'] as $ext) {
        $file = __DIR__ . '/../assets/images/recepten/' . $id . '.' . $ext;
        if (is_file($file)) {
            unlink($file);
        }
    }
}

function saveRecipePhoto(array $file, string $id): array
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'message' => ''];
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'De foto is te groot (maximaal 2 MB).'];
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Het uploaden van de foto is mislukt.'];
    }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]]) || $info[0] > 6000 || $info[1] > 6000) {
        return ['success' => false, 'message' => 'Gebruik een jpg-, png- of webp-foto.'];
    }
    $dir = __DIR__ . '/../assets/images/recepten';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $id . '.' . $types[$info[2]])) {
        return ['success' => false, 'message' => 'De foto kon niet worden opgeslagen.'];
    }
    return ['success' => true, 'message' => ''];
}

function addUserRecipe(PDO $conn, int $userId, array $file): array
{
    $fail = fn(string $message): array => ['success' => false, 'message' => $message];

    $titel   = trim(postValue('titel'));
    $intro   = trim(postValue('intro'));
    $cat     = postValue('cat');
    $land    = postValue('land');
    $niveau  = postValue('niveau');
    $eenheid = postValue('eenheid');
    $prep    = (int) postValue('prep');
    $kook    = (int) postValue('kook');
    $porties = (int) postValue('porties');

    if (preg_match('//u', $titel . $intro . postValue('ingredienten') . postValue('stappen')) !== 1) {
        return $fail('Gebruik alleen gewone tekst.');
    }
    if (textLength($titel) < 3 || textLength($titel) > 60) {
        return $fail('De titel moet 3 tot 60 tekens hebben.');
    }
    if (textLength($intro) > 300) {
        return $fail('De beschrijving mag maximaal 300 tekens zijn.');
    }
    if (!isset(RECIPE_CATEGORIES[$cat])) {
        return $fail('Kies een categorie.');
    }
    if ($land !== '' && !isset(RECIPE_COUNTRIES[$land])) {
        return $fail('Kies een land uit de lijst.');
    }
    if (!in_array($niveau, RECIPE_LEVELS, true) || !in_array($eenheid, RECIPE_UNITS, true)) {
        return $fail('Kies een moeilijkheid en een eenheid.');
    }
    if ($prep < 0 || $prep > 600 || $kook < 0 || $kook > 600) {
        return $fail('De tijden moeten tussen 0 en 600 minuten liggen.');
    }
    if ($porties < 1 || $porties > 50) {
        return $fail('Het aantal moet tussen 1 en 50 liggen.');
    }

    $lines = fn(string $text): array => array_values(array_filter(
        array_map('trim', preg_split('/\R/u', $text) ?: []),
        fn($line) => $line !== ''
    ));

    $ingLines = $lines(postValue('ingredienten'));
    if (count($ingLines) < 1 || count($ingLines) > 40) {
        return $fail('Voeg 1 tot 40 ingrediënten toe (één per regel).');
    }
    $ing = [];
    foreach ($ingLines as $line) {
        if (textLength($line) > 120) {
            return $fail('Een ingrediënt mag maximaal 120 tekens zijn.');
        }
        if (preg_match('/^(\d+(?:[.,]\d+)?)(?![\d\/.,])\s*(\S.*)$/u', $line, $m)) {
            $ing[] = ['n' => (float) str_replace(',', '.', $m[1]), 't' => $m[2]];
        } else {
            $ing[] = ['t' => $line];
        }
    }

    $stepLines = $lines(postValue('stappen'));
    if (count($stepLines) < 1 || count($stepLines) > 30) {
        return $fail('Voeg 1 tot 30 stappen toe (één per regel).');
    }
    $stappen = [];
    foreach ($stepLines as $i => $line) {
        if (textLength($line) > 600) {
            return $fail('Een stap mag maximaal 600 tekens zijn.');
        }
        if (preg_match('/^([^:]{1,60}):\s*(\S.*)$/u', $line, $m)) {
            $stappen[] = [trim($m[1]), $m[2]];
        } else {
            $stappen[] = ['Stap ' . ($i + 1), $line];
        }
    }

    $count = $conn->prepare("SELECT COUNT(*) FROM user_recipes WHERE user_id = :id");
    $count->execute(['id' => $userId]);
    if ((int) $count->fetchColumn() >= 20) {
        return $fail('Je kunt maximaal 20 eigen recepten hebben. Verwijder er eerst een.');
    }

    $ascii = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $titel) : $titel;
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $ascii)), '-');
    $id = rtrim(substr($slug !== '' ? $slug : 'recept', 0, 40), '-') . '-' . bin2hex(random_bytes(3));

    $photo = saveRecipePhoto($file, $id);
    if (!$photo['success']) {
        return $fail($photo['message']);
    }

    $recipe = [
        'id'      => $id,
        'titel'   => $titel,
        'cat'     => $cat,
        'tags'    => postValue('vegetarisch') === '1' ? ['Vegetarisch'] : [],
        'emoji'   => RECIPE_CATEGORIES[$cat],
        'intro'   => $intro,
        'info'    => [$prep . ' min voorbereiding', $kook > 0 ? $kook . ' min koken' : 'Geen baktijd', $niveau, $porties . ' ' . $eenheid],
        'basis'   => $porties,
        'opties'  => array_values(array_unique([max(1, (int) ceil($porties / 2)), $porties, $porties * 2])),
        'ing'     => $ing,
        'stappen' => $stappen,
    ];
    if ($land !== '') {
        $recipe['land'] = $land;
        $recipe['vlag'] = RECIPE_COUNTRIES[$land];
    }

    try {
        $conn->prepare("INSERT INTO user_recipes (id, user_id, data) VALUES (:id, :user, :data)")
             ->execute(['id' => $id, 'user' => $userId, 'data' => json_encode($recipe, JSON_UNESCAPED_UNICODE)]);
    } catch (PDOException $e) {
        deleteRecipePhotos($id);
        return $fail('Het recept kon niet worden opgeslagen.');
    }
    return ['success' => true, 'message' => 'Je recept is toegevoegd.'];
}

function deleteUserRecipe(PDO $conn, int $userId, string $id): bool
{
    if (!isValidRecipeId($id)) {
        return false;
    }
    $stmt = $conn->prepare("DELETE FROM user_recipes WHERE id = :id AND user_id = :user");
    $stmt->execute(['id' => $id, 'user' => $userId]);
    if ($stmt->rowCount() === 0) {
        return false;
    }
    $conn->prepare("DELETE FROM favorites WHERE recipe_id = :id")->execute(['id' => $id]);
    $conn->prepare("DELETE FROM comments WHERE recipe_id = :id")->execute(['id' => $id]);
    deleteRecipePhotos($id);
    return true;
}
