<?php
// Webhook WhatsApp entrant (à configurer dans la console Twilio)
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/whatsapp.php';

$numeroPropre = preg_replace('/[^0-9]/', '', $_POST['From'] ?? '');
$texte = trim($_POST['Body'] ?? '');

if ($numeroPropre !== '') {
    error_log("[WhatsApp entrant] De $numeroPropre : $texte");
    if (strtoupper($texte) === 'STATUT') {
        $stmt = $pdo->prepare("SELECT commandes.*, commercants.nom_boutique FROM commandes JOIN commercants ON commercants.id = commandes.commercant_id WHERE commandes.client_telephone LIKE ? ORDER BY commandes.date_creation DESC LIMIT 1");
        $stmt->execute(['%' . substr($numeroPropre, -8) . '%']);
        $c = $stmt->fetch();
        $libelles = ['nouvelle' => 'en attente de confirmation', 'confirmee' => 'confirmée', 'livree' => 'livrée', 'annulee' => 'annulée'];
        $reponse = $c ? "Ta dernière commande chez {$c['nom_boutique']} est : " . $libelles[$c['statut']] . "." : "Nous n'avons trouvé aucune commande associée à ce numéro.";
        envoyerMessageWhatsApp($numeroPropre, $reponse);
    }
}
header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="UTF-8"?><Response></Response>';
