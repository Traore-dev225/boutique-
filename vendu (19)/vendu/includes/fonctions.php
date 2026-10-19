<?php
function e($valeur) { return htmlspecialchars($valeur ?? '', ENT_QUOTES, 'UTF-8'); }

function formaterPrix($montant) { return number_format((float)$montant, 0, ',', ' ') . ' FCFA'; }

function genererSlug($texte) {
    $texte = strtolower(trim($texte));
    $texte = strtr($texte, ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n']);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $texte), '-');
}

function slugUnique($pdo, $slugBase) {
    $slug = $slugBase; $i = 1;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commercants WHERE slug = ?");
    while (true) {
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() == 0) return $slug;
        $i++; $slug = $slugBase . '-' . $i;
    }
}

function limiteProduitsAtteinte($pdo, $commercantId, $plan) {
    if ($plan !== 'gratuit') return false;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE commercant_id = ?");
    $stmt->execute([$commercantId]);
    return $stmt->fetchColumn() >= 10;
}

const PRIX_PLANS = ['pro' => 4000, 'business' => 10000];

function verifierExpirationAbonnement($pdo, $commercant) {
    if ($commercant['plan'] === 'gratuit' || $commercant['plan_date_fin'] === null) return $commercant;
    if (strtotime($commercant['plan_date_fin']) < strtotime('today')) {
        $stmt = $pdo->prepare("UPDATE commercants SET plan = 'gratuit', plan_date_fin = NULL WHERE id = ?");
        $stmt->execute([$commercant['id']]);
        $commercant['plan'] = 'gratuit'; $commercant['plan_date_fin'] = null;
    }
    return $commercant;
}

function telephoneVersLienWhatsApp($telephone, $message = '') {
    $numero = preg_replace('/[^0-9]/', '', $telephone);
    $lien = "https://wa.me/$numero";
    if ($message !== '') $lien .= '?text=' . rawurlencode($message);
    return $lien;
}

function redirigerVers($url) { header("Location: $url"); exit; }

function traiterUploadPhoto($fichier) {
    if (!isset($fichier) || $fichier['error'] !== UPLOAD_ERR_OK) return null;
    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg','jpeg','png','webp'])) return null;
    $nom = uniqid('produit_', true) . '.' . $extension;
    return move_uploaded_file($fichier['tmp_name'], __DIR__ . '/../assets/uploads/' . $nom) ? $nom : null;
}
