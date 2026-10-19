<?php
// Génération de fiche produit par IA (API Claude) — désactivée par défaut
define('IA_ACTIVE', false);
define('ANTHROPIC_API_KEY', 'TA_CLE_API_ICI');
define('ANTHROPIC_MODELE', 'claude-sonnet-4-6');

function genererFicheProduitDepuisPhoto($cheminPhoto) {
    if (!IA_ACTIVE || !file_exists($cheminPhoto)) return null;
    $extension = strtolower(pathinfo($cheminPhoto, PATHINFO_EXTENSION));
    $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $consigne = "Regarde cette photo de produit destiné à la vente en ligne en Côte d'Ivoire. Réponds UNIQUEMENT avec un objet JSON valide avec exactement ces clés : \"nom\", \"description\" (2-3 phrases), \"categorie\" (Vêtements, Chaussures, Accessoires, Cosmétiques, Bijoux, Électronique, Alimentation, ou Autre).";
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => ANTHROPIC_MODELE, 'max_tokens' => 500,
        'messages' => [['role' => 'user', 'content' => [
            ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mimes[$extension] ?? 'image/jpeg', 'data' => base64_encode(file_get_contents($cheminPhoto))]],
            ['type' => 'text', 'text' => $consigne],
        ]]],
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'x-api-key: ' . ANTHROPIC_API_KEY, 'anthropic-version: 2023-06-01']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $reponse = curl_exec($ch);
    curl_close($ch);
    $resultat = json_decode($reponse, true);
    if (!isset($resultat['content'][0]['text'])) return null;
    $texte = preg_replace('/^```json\s*|\s*```$/', '', trim($resultat['content'][0]['text']));
    $fiche = json_decode($texte, true);
    return isset($fiche['nom'], $fiche['description'], $fiche['categorie']) ? $fiche : null;
}
