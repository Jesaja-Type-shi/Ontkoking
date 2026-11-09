<?php

require_once __DIR__ . '/../includes/helpers.php';

[$user, $input] = apiStart($conn);
$id = is_string($input['id'] ?? null) ? $input['id'] : '';
$tekst = is_string($input['tekst'] ?? null) ? trim($input['tekst']) : '';
if (!isValidRecipeId($id)) {
    apiStop(400, 'Ongeldig recept.');
}
if ($tekst === '' || strlen($tekst) > 1000 || preg_match('//u', $tekst) !== 1) {
    apiStop(400, 'Schrijf een reactie van maximaal 500 tekens.');
}
$userId = (int) $user['id'];


$count = $conn->prepare("SELECT COUNT(*) FROM comments WHERE user_id = :user AND created_at > datetime('now', '-1 minute')");
$count->execute(['user' => $userId]);
if ((int) $count->fetchColumn() >= 5) {
    apiStop(429, 'Rustig aan, probeer het over een minuutje opnieuw.');
}

$conn->prepare("INSERT INTO comments (user_id, recipe_id, body) VALUES (:user, :recept, :tekst)")
     ->execute(['user' => $userId, 'recept' => $id, 'tekst' => $tekst]);
echo json_encode(['reacties' => getComments($conn, $id)]);
