<?php
// Intégration WhatsApp Business via Twilio (voir README pour l'activation)
define('WHATSAPP_ACTIF', false);
define('TWILIO_ACCOUNT_SID', 'TON_ACCOUNT_SID_ICI');
define('TWILIO_AUTH_TOKEN', 'TON_AUTH_TOKEN_ICI');
define('TWILIO_NUMERO_WHATSAPP', 'whatsapp:+14155238886');

function envoyerMessageWhatsApp($numeroDestinataire, $message) {
    if (!WHATSAPP_ACTIF) {
        error_log("[WhatsApp désactivé] Message non envoyé à $numeroDestinataire : $message");
        return false;
    }
    $numero = preg_replace('/[^0-9]/', '', $numeroDestinataire);
    $url = "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . "/Messages.json";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'From' => TWILIO_NUMERO_WHATSAPP, 'To' => 'whatsapp:+' . $numero, 'Body' => $message,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $reponse = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreur = curl_error($ch);
    curl_close($ch);
    if ($erreur) { error_log("[WhatsApp] cURL : $erreur"); return false; }
    if ($code >= 200 && $code < 300) return true;
    error_log("[WhatsApp] Échec (HTTP $code) : $reponse");
    return false;
}

function notifierNouvelleCommande($commercant, $commande) {
    $message = "🛎️ Nouvelle commande sur Vendu !\nClient : {$commande['client_nom']}\nTéléphone : {$commande['client_telephone']}\nMontant : " . formaterPrix($commande['montant_total']) . "\nConnecte-toi à ton tableau de bord pour la traiter.";
    return envoyerMessageWhatsApp($commercant['telephone'], $message);
}

function notifierChangementStatutCommande($commande, $nomBoutique) {
    $libelles = ['confirmee' => "confirmée ✅", 'livree' => "livrée 📦", 'annulee' => "annulée ❌"];
    if (!isset($libelles[$commande['statut']])) return false;
    $message = "Bonjour {$commande['client_nom']}, votre commande chez $nomBoutique est maintenant " . $libelles[$commande['statut']] . ".";
    return envoyerMessageWhatsApp($commande['client_telephone'], $message);
}

function notifierExpirationProchaine($commercant, $joursRestants) {
    $message = "⏰ Ton abonnement " . ucfirst($commercant['plan']) . " sur Vendu expire dans $joursRestants jour(s). Renouvelle-le depuis ton tableau de bord.";
    return envoyerMessageWhatsApp($commercant['telephone'], $message);
}
