<?php
// auth.php (root-niveau)
// Alle logica voor de inlogfunctie. Maakt gebruik van de $conn uit includes/dbconfig.php,
// zonder dbconfig.php zelf aan te passen.

require_once __DIR__ . '/includes/dbconfig.php';

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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Registreert een nieuwe gebruiker.
 * Geeft een array terug: ['success' => bool, 'message' => string]
 */
function registerUser(PDO $conn, string $username, string $email, string $password): array
{
    $username = trim($username);
    $email = trim($email);

    if ($username === '' || $email === '' || $password === '') {
        return ['success' => false, 'message' => 'Vul alle velden in.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Vul een geldig e-mailadres in.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Wachtwoord moet minimaal 6 tekens bevatten.'];
    }

    // Controleer of username of email al bestaat
    $stmt = $conn->prepare("SELECT id FROM login WHERE username = :username OR email = :email");
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
 * Geeft een array terug: ['success' => bool, 'message' => string]
 */
function loginUser(PDO $conn, string $usernameOrEmail, string $password): array
{
    $usernameOrEmail = trim($usernameOrEmail);

    if ($usernameOrEmail === '' || $password === '') {
        return ['success' => false, 'message' => 'Vul alle velden in.'];
    }

    $stmt = $conn->prepare("SELECT * FROM login WHERE username = :value OR email = :value");
    $stmt->execute(['value' => $usernameOrEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Gebruikersnaam/e-mail of wachtwoord is onjuist.'];
    }

    // Sessie vaststellen
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

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
