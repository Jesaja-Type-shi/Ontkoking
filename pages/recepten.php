<?php
$paginaTitel = 'Recepten';
$css  = 'style.css';
$base = '../';
$scripts = ['recepten.js'];
include __DIR__ . '/../includes/header.php';
?>

<main>
    <div class="titelrij"><h1>Alle recepten</h1></div>
    <div class="chips-rij" id="cats"></div>
    <div class="chips-rij" id="landen"></div>
    <div class="raster" id="raster"></div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
