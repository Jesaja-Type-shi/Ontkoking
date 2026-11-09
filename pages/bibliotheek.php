<?php
require_once __DIR__ . '/../includes/helpers.php';

// Alleen ingelogde gebruikers hebben een bibliotheek
if (!isLoggedIn()) {
    header('Location: login.php?terug=bibliotheek.php');
    exit;
}

$paginaTitel = 'Mijn bibliotheek';
$css  = 'style.css';
$base = '../';
$scripts = ['bibliotheek.js'];
include __DIR__ . '/../includes/header.php';
?>

<main>
    <div class="titelrij"><h1>Mijn bibliotheek</h1><p>Toegevoegde favoriete gerechten</p></div>
    <div class="raster groot" id="raster"></div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
