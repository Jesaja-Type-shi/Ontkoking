<?php
$paginaTitel = 'Recepten';
$css  = 'recepten.css';
$base = '../';

// Later kun je deze array vervangen door een database-query
$recepten = [
    ['titel' => 'Pasta Carbonara',     'afbeelding' => 'pasta.jpg',      'tijd' => '25 minuten', 'niveau' => 'Makkelijk', 'link' => 'receptenDetail/pasta.html'],
    ['titel' => 'Chicken Wrap',        'afbeelding' => 'wrap.jpeg',      'tijd' => '20 minuten', 'niveau' => 'Makkelijk', 'link' => 'receptenDetail/wrap.html'],
    ['titel' => 'Poké Bowl',           'afbeelding' => 'poke-bowl.jpeg', 'tijd' => '15 minuten', 'niveau' => 'Makkelijk', 'link' => 'receptenDetail/pokeBowl.html'],
    ['titel' => 'Zelfgemaakte Burger', 'afbeelding' => 'burger.jpeg',    'tijd' => '30 minuten', 'niveau' => 'Gemiddeld', 'link' => 'receptenDetail/burger.html'],
    ['titel' => 'Pancakes',            'afbeelding' => 'pancakes.jpeg',  'tijd' => '15 minuten', 'niveau' => 'Makkelijk', 'link' => 'receptenDetail/pancakes.html'],
    ['titel' => 'Brownies',            'afbeelding' => 'brownie.jpeg',  'tijd' => '35 minuten', 'niveau' => 'Gemiddeld', 'link' => 'receptenDetail/brownies.html'],
];

$categorieen = ['Alles', 'Ontbijt', 'Lunch', 'Diner', 'Snacks', 'Dessert'];

// Eenvoudig zoeken op titel
$zoek = trim($_GET['zoek'] ?? '');
if ($zoek !== '') {
    $recepten = array_filter($recepten, fn($r) => stripos($r['titel'], $zoek) !== false);
}

include __DIR__ . '/../includes/header.php';
?>

<main>

    <section class="intro">
        <h2>Ontdek recepten</h2>
        <p>Bekijk verschillende recepten en vind iets lekkers om zelf te maken.</p>
    </section>

    <section class="zoeken">
        <form method="get" action="recepten.php">
            <input type="text" name="zoek" placeholder="Zoek een recept..." value="<?= htmlspecialchars($zoek) ?>">
            <button type="submit">Zoeken</button>
        </form>
    </section>

    <section class="categorieen">
        <?php foreach ($categorieen as $categorie): ?>
            <button type="button"><?= htmlspecialchars($categorie) ?></button>
        <?php endforeach; ?>
    </section>

    <section class="recepten">
        <h2>Recepten</h2>

        <?php if (empty($recepten)): ?>
            <p>Geen recepten gevonden voor "<?= htmlspecialchars($zoek) ?>".</p>
        <?php else: ?>
        <div class="recept-container">
            <?php foreach ($recepten as $recept): ?>
                <div class="recept">
                    <img src="<?= $base ?>assets/images/<?= htmlspecialchars($recept['afbeelding']) ?>" alt="<?= htmlspecialchars($recept['titel']) ?>">
                    <h3><?= htmlspecialchars($recept['titel']) ?></h3>
                    <p><?= htmlspecialchars($recept['tijd']) ?> &middot; <?= htmlspecialchars($recept['niveau']) ?></p>
                    <a href="<?= htmlspecialchars($recept['link']) ?>">Bekijk recept</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
