<?php
require_once __DIR__ . '/../includes/helpers.php';

$id = isset($_GET['id']) && is_string($_GET['id']) && isValidRecipeId($_GET['id']) ? $_GET['id'] : '';

$paginaTitel = 'Recept';
$css  = 'style.css';
$base = '../';
$scripts = ['detail.js'];
$paginaData = ['reacties' => $id !== '' ? getComments($conn, $id) : []];
include __DIR__ . '/../includes/header.php';
?>

<main id="detail"></main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
