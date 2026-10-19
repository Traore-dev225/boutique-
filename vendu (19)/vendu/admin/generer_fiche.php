<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/ai.php';
header('Content-Type: application/json');
exigerConnexion();

if (!IA_ACTIVE) { echo json_encode(['erreur' => "La génération par IA n'est pas encore activée sur ce site."]); exit; }
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) { echo json_encode(['erreur' => "Merci de choisir une photo d'abord."]); exit; }
$ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg','jpeg','png','webp'])) { echo json_encode(['erreur' => "Format de photo non supporté."]); exit; }

$tmp = sys_get_temp_dir() . '/' . uniqid('ia_produit_', true) . '.' . $ext;
move_uploaded_file($_FILES['photo']['tmp_name'], $tmp);
$fiche = genererFicheProduitDepuisPhoto($tmp);
@unlink($tmp);
echo $fiche ? json_encode(['succes' => true, 'fiche' => $fiche]) : json_encode(['erreur' => "La génération a échoué, merci de remplir la fiche manuellement."]);
