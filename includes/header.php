<?php
// Gedeelde header.
// Zet VOOR het includen:
//   $paginaTitel = 'Titel';
//   $css         = 'recepten.css';   (bestand in assets/css/)
//   $base        = '../';            (pad terug naar de projectmap: '../' vanuit pages/, '../../' vanuit pages/receptenDetail/)
require_once __DIR__ . '/auth.php';

$paginaTitel = $paginaTitel ?? 'Ontkoking';
$css         = $css ?? 'recepten.css';
$base        = $base ?? '../';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($paginaTitel) ?> | Ontkoking</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/<?= htmlspecialchars($css) ?>">
    <link rel="icon" type="image/png" href="<?= $base ?>assets/images/logo.png">
</head>
<body>
<header>
    <h1>Ontkoking</h1>

    <nav aria-label="Hoofdnavigatie">
        <a href="<?= $base ?>index.php">Home</a>
        <a href="<?= $base ?>pages/recepten.php">Recepten</a>
        <a href="<?= $base ?>pages/invoer.php">Recepten toevoegen</a>
        <?php if (isLoggedIn()): ?>
            <span class="account">Welkom, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <a href="<?= $base ?>pages/logout.php">Uitloggen</a>
        <?php else: ?>
            <a href="<?= $base ?>pages/login.php">Inloggen</a>
        <?php endif; ?>
    </nav>
</header>
