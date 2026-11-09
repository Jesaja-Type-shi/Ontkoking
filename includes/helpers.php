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
