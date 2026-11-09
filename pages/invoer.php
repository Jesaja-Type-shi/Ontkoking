<?php
require_once __DIR__ . '/../includes/auth.php';


if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$paginaTitel = 'Recepten toevoegen';
$css  = 'style.css';
$base = '../';
include __DIR__ . '/../includes/header.php';
?>

<main>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
