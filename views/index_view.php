<?php
$paginaTitel = 'Home';
$css  = 'style.css';
$base = './';
$scripts = ['home.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="hero" id="hero">
  <div><h1>Wat ga jij vandaag koken?</h1><p>Ontdek makkelijke recepten en bewaar je favorieten in je eigen bibliotheek.</p><a class="knop" href="<?= $base ?>pages/recepten.php">Bekijk alle recepten</a></div>
</section>
<section class="rondjes">
<?php

$categorieen = ['Ontbijt' => '🥞', 'Lunch' => '🥪', 'Diner' => '🍝', 'Soep' => '🍲', 'Salade' => '🥗',
                'Snacks' => '🍿', 'Dessert' => '🍰', 'Drankjes' => '🥤', 'Vegetarisch' => '🥦'];
foreach ($categorieen as $naam => $emoji):
    $foto = '';
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $pad = 'assets/images/categorieen/' . strtolower($naam) . '.' . $ext;
        if (is_file(__DIR__ . '/../' . $pad)) {
            $foto = $pad;
            break;
        }
    }
?>
  <a class="rondje" href="<?= $base ?>pages/recepten.php?cat=<?= urlencode($naam) ?>"><span><?php if ($foto !== ''): ?><img src="<?= $base . $foto ?>" alt=""><?php else: ?><?= $emoji ?><?php endif; ?></span><?= e($naam) ?></a>
<?php endforeach; ?>
</section>
<section class="wereld" id="wereld"><h2>Ontdek de wereldkeuken</h2><div class="rondjes" id="landen"></div></section>
<section class="donker" id="populair-recepten"><div class="binnen"><h2>Populaire recepten</h2><div class="raster" id="populair"></div></div></section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
