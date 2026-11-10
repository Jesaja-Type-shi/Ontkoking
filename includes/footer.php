<?php
$base = $base ?? '../';

$contactEmail = 'ontkoking@gmail.com';
$fotos = [];
$fotoMap = __DIR__ . '/../assets/images/recepten';
if (is_dir($fotoMap)) {
    foreach (scandir($fotoMap) as $bestand) {
        $ext = strtolower(pathinfo($bestand, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $fotos[strtolower(pathinfo($bestand, PATHINFO_FILENAME))] = 'assets/images/recepten/' . $bestand;
        }
    }
}
$landFotos = [];
$landMap = __DIR__ . '/../assets/images/landen';
if (is_dir($landMap)) {
    foreach (scandir($landMap) as $bestand) {
        $ext = strtolower(pathinfo($bestand, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $landFotos[strtolower(pathinfo($bestand, PATHINFO_FILENAME))] = 'assets/images/landen/' . $bestand;
        }
    }
}
$app = [
    'base'      => $base,
    'gebruiker' => $gebruiker['username'] ?? null,
    'favs'      => !empty($gebruiker) ? getFavorites($conn, (int) $gebruiker['id']) : [],
    'csrf'      => csrfToken(),
    'fotos'     => (object) $fotos,
    'landfotos' => (object) $landFotos,
    'eigen'     => getUserRecipes($conn),
];
?>
<footer id="voet">
    <div class="voet-raster">
        <div>
            <h3>Ontkoking</h3>
            <p>Makkelijke recepten uit de hele wereld. Bewaar je favorieten in je eigen bibliotheek.</p>
        </div>
        <div>
            <h3>Menu</h3>
            <ul>
                <li><a href="<?= $base ?>index.php">Home</a></li>
                <li><a href="<?= $base ?>pages/recepten.php">Recepten</a></li>
                <li><a href="<?= $base ?>pages/bibliotheek.php">Mijn bibliotheek</a></li>
            </ul>
        </div>
        <div>
            <h3>Contact</h3>
            <p>Een vraag of een tip?<br>Stuur ons een mail:</p>
            <p><a class="voet-mail" href="mailto:<?= e($contactEmail) ?>?subject=Contact%20Ontkoking">✉ <?= e($contactEmail) ?></a></p>
        </div>
    </div>
</footer>
<div id="toast" role="status" aria-live="polite"></div>

<script>
const APP = <?= jsonForHtml($app) ?>;
const PAGE = <?= jsonForHtml($paginaData ?? new stdClass()) ?>;
</script>
<script src="<?= $base ?>assets/js/recipes.js"></script>
<script src="<?= $base ?>assets/js/app.js"></script>
<?php foreach (($scripts ?? []) as $script): ?>
<script src="<?= $base ?>assets/js/<?= e($script) ?>"></script>
<?php endforeach; ?>

</body>
</html>
