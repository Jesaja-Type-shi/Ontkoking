<?php
require_once __DIR__ . '/../includes/recipes.php';

$paginaTitel = 'Recepten';
$css  = 'recepten.css';
$base = '../';

// Voorbeeldrecepten (vaste pagina's)
$voorbeelden = [
    ['titel' => 'Pasta Carbonara',     'afbeelding' => 'pasta.jpg',      'tijd' => '25 minuten', 'label' => 'Makkelijk', 'categorie' => 'Diner',   'link' => 'receptenDetail/pasta.html'],
    ['titel' => 'Chicken Wrap',        'afbeelding' => 'wrap.jpeg',      'tijd' => '20 minuten', 'label' => 'Makkelijk', 'categorie' => 'Lunch',   'link' => 'receptenDetail/wrap.html'],
    ['titel' => 'Poké Bowl',           'afbeelding' => 'poke-bowl.jpeg', 'tijd' => '15 minuten', 'label' => 'Makkelijk', 'categorie' => 'Lunch',   'link' => 'receptenDetail/pokeBowl.html'],
    ['titel' => 'Zelfgemaakte Burger', 'afbeelding' => 'burger.jpeg',    'tijd' => '30 minuten', 'label' => 'Gemiddeld', 'categorie' => 'Diner',   'link' => 'receptenDetail/burger.html'],
    ['titel' => 'Pancakes',            'afbeelding' => 'pancakes.jpeg',  'tijd' => '15 minuten', 'label' => 'Makkelijk', 'categorie' => 'Ontbijt', 'link' => 'receptenDetail/pancakes.html'],
    ['titel' => 'Brownies',            'afbeelding' => 'brownie.jpeg',   'tijd' => '35 minuten', 'label' => 'Gemiddeld', 'categorie' => 'Dessert', 'link' => 'receptenDetail/brownies.html'],
];

$categorieen = array_merge(['Alles'], RECEPT_CATEGORIEEN);

$zoek      = trim($_GET['zoek'] ?? '');
$categorie = trim($_GET['categorie'] ?? '');
if (!in_array($categorie, RECEPT_CATEGORIEEN, true)) {
    $categorie = '';
}

// Voorbeelden filteren
$recepten = array_filter($voorbeelden, function ($r) use ($zoek, $categorie) {
    return ($zoek === '' || stripos($r['titel'], $zoek) !== false)
        && ($categorie === '' || $r['categorie'] === $categorie);
});

// Recepten uit de database erbij
foreach (getRecipes($conn, $zoek, $categorie) as $r) {
    $recepten[] = [
        'titel'      => $r['naam'],
        'afbeelding' => $r['foto'] ?: null,
        'map'        => 'uploads',
        'tijd'       => (int)$r['tijd'] . ' minuten',
        'label'      => $r['categorie'],
        'auteur'     => $r['username'] ?? null,
        'link'       => 'detail.php?id=' . (int)$r['id'],
    ];
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
            <?php if ($categorie !== ''): ?>
                <input type="hidden" name="categorie" value="<?= htmlspecialchars($categorie) ?>">
            <?php endif; ?>
            <button type="submit">Zoeken</button>
        </form>
    </section>

    <section class="categorieen">
        <form method="get" action="recepten.php">
            <?php if ($zoek !== ''): ?>
                <input type="hidden" name="zoek" value="<?= htmlspecialchars($zoek) ?>">
            <?php endif; ?>
            <?php foreach ($categorieen as $cat): ?>
                <button type="submit" name="categorie" value="<?= $cat === 'Alles' ? '' : htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
            <?php endforeach; ?>
        </form>
    </section>

    <section class="recepten">
        <h2>Recepten</h2>

        <?php if (empty($recepten)): ?>
            <p>Geen recepten gevonden.</p>
        <?php else: ?>
        <div class="recept-container">
            <?php foreach ($recepten as $recept): ?>
                <div class="recept">
                    <?php if (!empty($recept['afbeelding'])): ?>
                        <img src="<?= $base ?>assets/<?= htmlspecialchars($recept['map'] ?? 'images') ?>/<?= htmlspecialchars($recept['afbeelding']) ?>" alt="<?= htmlspecialchars($recept['titel']) ?>">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($recept['titel']) ?></h3>
                    <p>
                        <?= htmlspecialchars($recept['tijd']) ?> &middot; <?= htmlspecialchars($recept['label']) ?>
                        <?php if (!empty($recept['auteur'])): ?>
                            &middot; door <?= htmlspecialchars($recept['auteur']) ?>
                        <?php endif; ?>
                    </p>
                    <a href="<?= htmlspecialchars($recept['link']) ?>">Bekijk recept</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>