<?php
// Gedeelde header.
// Zet VOOR het includen:
//   $paginaTitel = 'Titel';
//   $css         = 'style.css';      (bestand in assets/css/)
//   $base        = '../';            (pad terug naar de projectmap: '../' vanuit pages/, './' vanuit index.php)
require_once __DIR__ . '/helpers.php';

$paginaTitel = $paginaTitel ?? 'Ontkoking';
$css         = $css ?? 'style.css';
$base        = $base ?? '../';

$huidig    = basename($_SERVER['SCRIPT_NAME'] ?? '');
$gebruiker = currentUser($conn);
$aantalFav = $gebruiker ? count(getFavorites($conn, (int) $gebruiker['id'])) : 0;
$menu = [
    // [tekst, link, bestandsnaam voor de actieve stand ('' = geen)]
    ['Home',         $base . 'index.php',                    'index.php'],
    ['Recepten',     $base . 'pages/recepten.php',           'recepten.php'],
    ['Populair',     $base . 'index.php#populair-recepten',  ''],
    ['Mijn bibliotheek', $base . 'pages/bibliotheek.php',    'bibliotheek.php'],
];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($paginaTitel) ?> | Ontkoking</title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/<?= e($css) ?>">
    <link rel="icon" type="image/png" href="<?= $base ?>assets/images/logo.png">
</head>
<body>
<header id="kop">
    <div class="topbar">
        <a class="logo" href="<?= $base ?>index.php"><span>Ontkoking</span></a>

        <nav class="menu" aria-label="Hoofdnavigatie">
            <?php foreach ($menu as [$tekst, $href, $bestand]): ?>
                <a href="<?= $href ?>" class="<?= $bestand !== '' && $huidig === $bestand ? 'actief' : '' ?>"><?= $tekst ?><?php if ($bestand === 'bibliotheek.php'): ?> <b id="telling"<?= $aantalFav ? '' : ' hidden' ?>><?= $aantalFav ?></b><?php endif; ?></a>
            <?php endforeach; ?>
            <?php if ($gebruiker): ?>
                <a class="alleen-mobiel" href="<?= $base ?>pages/logout.php">Uitloggen (<?= e($gebruiker['username']) ?>)</a>
            <?php endif; ?>
        </nav>

        <div class="acties">
            <?php if ($gebruiker): ?>
                <a class="profiel" href="<?= $base ?>pages/account.php" title="Mijn account (<?= e($gebruiker['username']) ?>)" aria-label="Mijn account"><?= e(strtoupper($gebruiker['username'][0])) ?></a>
                <a class="uitlog-link" href="<?= $base ?>pages/logout.php">Uitloggen</a>
            <?php else: ?>
                <a class="inlog-knop" href="<?= $base ?>pages/login.php">Inloggen</a>
            <?php endif; ?>
            <button type="button" class="menu-knop" aria-label="Menu openen" aria-expanded="false"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
        </div>
    </div>

    <form class="zoek" id="zoekform" action="<?= $base ?>pages/recepten.php" method="get">
        <input type="search" id="zoekveld" name="zoek" placeholder="Zoek een recept of ingrediënt..." aria-label="Zoek een recept">
    </form>
</header>
