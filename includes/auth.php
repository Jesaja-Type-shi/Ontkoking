<?php
// includes/auth.php
// Alle logica voor de inlogfunctie. Maakt gebruik van de $conn uit includes/dbconfig.php.

require_once __DIR__ . '/dbconfig.php';

$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$conn->exec('PRAGMA foreign_keys = ON');

// De 'login'-tabel wordt hier aangemaakt (dbconfig.php verwijst er via de
// FOREIGN KEY al naar, maar maakt hem zelf niet aan).
$conn->exec("CREATE TABLE IF NOT EXISTS login (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Bibliotheek (favorieten), reacties en inlogpogingen
$conn->exec("CREATE TABLE IF NOT EXISTS favorites (
    user_id INTEGER NOT NULL,
    recipe_id TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, recipe_id),
    FOREIGN KEY (user_id) REFERENCES login(id) ON DELETE CASCADE
)");
$conn->exec("CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    recipe_id TEXT NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES login(id) ON DELETE CASCADE
)");
$conn->exec("CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_key TEXT NOT NULL,
    attempted_at INTEGER NOT NULL
)");

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/**
 * Registreert een nieuwe gebruiker.
 * Geeft een array terug: ['success' => bool, 'message' => string]
 */
function registerUser(PDO $conn, string $username, string $email, string $password): array
{
    $username = trim($username);
    $email = strtolower(trim($email));

    if ($username === '' || $email === '' || $password === '') {
        return ['success' => false, 'message' => 'Vul alle velden in.'];
    }

    if (preg_match('/^[A-Za-z0-9_-]{3,20}$/', $username) !== 1) {
        return ['success' => false, 'message' => 'Gebruikersnaam: 3 tot 20 tekens (letters, cijfers, _ of -).'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        return ['success' => false, 'message' => 'Vul een geldig e-mailadres in.'];
    }

    if (strlen($password) < 8 || strlen($password) > 72) {
        return ['success' => false, 'message' => 'Wachtwoord moet 8 tot 72 tekens bevatten.'];
    }

    // Controleer of username of email al bestaat (hoofdletters maken niet uit)
    $stmt = $conn->prepare("SELECT id FROM login WHERE LOWER(username) = LOWER(:username) OR LOWER(email) = LOWER(:email)");
    $stmt->execute(['username' => $username, 'email' => $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Gebruikersnaam of e-mailadres is al in gebruik.'];
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO login (username, email, password) VALUES (:username, :email, :password)");
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password' => $hashedPassword,
    ]);

    return ['success' => true, 'message' => 'Account succesvol aangemaakt. Je kunt nu inloggen.'];
}

/**
 * Logt een gebruiker in op basis van gebruikersnaam of e-mail + wachtwoord.
 * Zet bij succes de sessievariabelen.
 * Maximaal 5 mislukte pogingen per 15 minuten (per IP + gebruikersnaam).
 * Geeft een array terug: ['success' => bool, 'message' => string]
 */
function loginUser(PDO $conn, string $usernameOrEmail, string $password): array
{
    $usernameOrEmail = trim($usernameOrEmail);

    if ($usernameOrEmail === '' || $password === '') {
        return ['success' => false, 'message' => 'Vul alle velden in.'];
    }

    $key = ($_SERVER['REMOTE_ADDR'] ?? '?') . '|' . strtolower($usernameOrEmail);
    $conn->prepare("DELETE FROM login_attempts WHERE attempted_at < :tijd")->execute(['tijd' => time() - 900]);
    $count = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE attempt_key = :key");
    $count->execute(['key' => $key]);
    if ((int) $count->fetchColumn() >= 5) {
        return ['success' => false, 'message' => 'Te veel mislukte pogingen. Probeer het over 15 minuten opnieuw.'];
    }

    $stmt = $conn->prepare("SELECT * FROM login WHERE LOWER(username) = LOWER(:naam) OR LOWER(email) = LOWER(:email)");
    $stmt->execute(['naam' => $usernameOrEmail, 'email' => $usernameOrEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        $conn->prepare("INSERT INTO login_attempts (attempt_key, attempted_at) VALUES (:key, :tijd)")
             ->execute(['key' => $key, 'tijd' => time()]);
        return ['success' => false, 'message' => 'Gebruikersnaam/e-mail of wachtwoord is onjuist.'];
    }

    $conn->prepare("DELETE FROM login_attempts WHERE attempt_key = :key")->execute(['key' => $key]);

    // Sessie vaststellen
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));

    return ['success' => true, 'message' => 'Succesvol ingelogd.'];
}

/**
 * Logt de huidige gebruiker uit.
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Controleert of er een gebruiker is ingelogd.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Controleert of de ingelogde gebruiker een beheerder is.
 */
function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Wijzigt het wachtwoord van een gebruiker (huidig wachtwoord is vereist).
 */
function changePassword(PDO $conn, int $userId, string $current, string $new): array
{
    if (strlen($new) < 8 || strlen($new) > 72) {
        return ['success' => false, 'message' => 'Je nieuwe wachtwoord moet 8 tot 72 tekens hebben.'];
    }
    $stmt = $conn->prepare("SELECT password FROM login WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($current, $hash)) {
        return ['success' => false, 'message' => 'Je huidige wachtwoord klopt niet.'];
    }
    $conn->prepare("UPDATE login SET password = :password WHERE id = :id")
         ->execute(['password' => password_hash($new, PASSWORD_DEFAULT), 'id' => $userId]);
    session_regenerate_id(true);
    return ['success' => true, 'message' => 'Je wachtwoord is gewijzigd.'];
}

/**
 * Verwijdert het account met alles wat erbij hoort (favorieten, reacties, producten).
 */
function deleteAccount(PDO $conn, int $userId, string $password): array
{
    $stmt = $conn->prepare("SELECT password FROM login WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($password, $hash)) {
        return ['success' => false, 'message' => 'Je wachtwoord klopt niet.'];
    }
    try {
        $conn->beginTransaction();
        $conn->prepare("DELETE FROM products WHERE user_id = :id")->execute(['id' => $userId]);
        $conn->prepare("DELETE FROM login WHERE id = :id")->execute(['id' => $userId]);
        $conn->commit();
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        return ['success' => false, 'message' => 'Het account kon niet worden verwijderd.'];
    }
    return ['success' => true, 'message' => ''];
}
