<?php
require_once __DIR__ . '/../includes/recipes.php';

$recept = getRecipe($conn, (int)($_GET['id'] ?? 0));

$paginaTitel = $recept ? $recept['naam'] : 'Recept niet gevonden';
$css  = 'detail.css';
$base = '../';
include __DIR__ . '/../includes/header.php';
?>

<main>
    <link rel="stylesheet" href="../pages/detail.css">
    <?php if (!$recept): ?>
        <article class="recept-detail">
            <h2>Recept niet gevonden</h2>
            <p>Dit recept bestaat niet (meer).</p>
            <a class="terug" href="recepten.php">&larr; Terug naar recepten</a>
        </article>
    <?php else: ?>
        <article class="recept-detail">
            <?php if (!empty($recept['foto'])): ?>
                <img src="<?= $base ?>assets/uploads/<?= htmlspecialchars($recept['foto']) ?>" alt="<?= htmlspecialchars($recept['naam']) ?>">
            <?php endif; ?>
            <h2><?= htmlspecialchars($recept['naam']) ?></h2>
            <p class="meta">
                <?= htmlspecialchars($recept['categorie']) ?> &middot; <?= (int)$recept['tijd'] ?> minuten
                <?php if (!empty($recept['username'])): ?>
                    &middot; door <?= htmlspecialchars($recept['username']) ?>
                <?php endif; ?>
            </p>

            <h3>Ingrediënten</h3>
            <p><?= htmlspecialchars($recept['ingredienten']) ?></p>

            <h3>Bereidingswijze</h3>
            <p><?= htmlspecialchars($recept['bereiding']) ?></p>

            <a class="terug" href="recepten.php">&larr; Terug naar recepten</a>
            <?php if (canEditRecipe($recept)): ?>
                &nbsp;&middot;&nbsp; <a class="terug" href="invoer.php?bewerk=<?= (int)$recept['id'] ?>">Aanpassen</a>
            <?php endif; ?>
        </article>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>