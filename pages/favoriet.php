<?php
// JSON-endpoint: zet een recept in of uit de bibliotheek van de ingelogde gebruiker.
require_once __DIR__ . '/../includes/helpers.php';

[$user, $input] = apiStart($conn);
$id = is_string($input['id'] ?? null) ? $input['id'] : '';
if (!isValidRecipeId($id)) {
    apiStop(400, 'Ongeldig recept.');
}
$userId = (int) $user['id'];

// Staat het recept al in de bibliotheek? Dan verwijderen, anders toevoegen.
$del = $conn->prepare("DELETE FROM favorites WHERE user_id = :user AND recipe_id = :recept");
$del->execute(['user' => $userId, 'recept' => $id]);
$aan = false;
if ($del->rowCount() === 0) {
    if (count(getFavorites($conn, $userId)) >= 200) {
        apiStop(400, 'Je bibliotheek is vol (maximaal 200 recepten).');
    }
    $conn->prepare("INSERT INTO favorites (user_id, recipe_id) VALUES (:user, :recept)")
         ->execute(['user' => $userId, 'recept' => $id]);
    $aan = true;
}
echo json_encode(['aan' => $aan, 'favs' => getFavorites($conn, $userId)]);
