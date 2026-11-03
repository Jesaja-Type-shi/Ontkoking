<?php
require_once __DIR__ . '/../includes/auth.php';
 
// Alleen ingelogde gebruikers mogen recepten toevoegen
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
 
$paginaTitel = 'Recepten toevoegen';
$css  = 'invoer.css';
$base = '../';
include __DIR__ . '/../includes/header.php';
?>
 
<main>
    <link rel="stylesheet" href="invoer.css">
    <section class="intro">
        <h2>Voeg jouw recept toe</h2>
        <p>Deel je favoriete gerechten</p>
    </section>
 
    <section class="formulier">
        <h2>Nieuw recept</h2>
 
        <form id="receptForm">
            <label for="naam">Naam van het recept</label>
            <input type="text" id="naam" required>
 
            <label for="categorie">Categorie</label>
            <select id="categorie" required>
                <option value="">Kies een categorie</option>
                <option>Ontbijt</option>
                <option>Lunch</option>
                <option>Diner</option>
                <option>Snacks</option>
                <option>Dessert</option>
            </select>
 
            <label for="tijd">Bereidingstijd in minuten</label>
            <input type="number" id="tijd" min="1" required>
 
            <label for="ingredienten">Ingrediënten</label>
            <textarea id="ingredienten"
                placeholder="Bijvoorbeeld:&#10;200 gram pasta&#10;2 eieren"
                required></textarea>
 
            <label for="bereiding">Bereidingswijze</label>
            <textarea id="bereiding"
                placeholder="Beschrijf de stappen van je recept..."
                required></textarea>
 
            <button type="submit" id="opslaan">Recept toevoegen</button>
        </form>
 
        <p id="melding"></p>
    </section>
 
    <section class="overzicht">
        <h2>Mijn recepten</h2>
        <div id="recepten"></div>
    </section>
</main>
 
<!-- JavaScript staat na de HTML-elementen, zodat die al bestaan als het script draait -->
<script src="invoer.js"></script>
 
<?php include __DIR__ . '/../includes/footer.php'; ?>