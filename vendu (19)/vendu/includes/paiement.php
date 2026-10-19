<?php
// Paiement Mobile Money via CinetPay (voir README pour l'activation)
define('PAIEMENT_ACTIF', false);
define('CINETPAY_API_KEY', 'TA_CLE_API_ICI');
define('CINETPAY_SITE_ID', 'TON_SITE_ID_ICI');
define('CINETPAY_URL_NOTIFICATION', 'https://ton-domaine.com/public/paiement_retour.php');
define('CINETPAY_URL_RETOUR', 'https://ton-domaine.com/public/paiement_retour.php');

function initierPaiementMobileMoney($reference, $montant, $description) {
    if (!PAIEMENT_ACTIF) {
        error_log("[Paiement désactivé] Paiement non initié pour $reference");
        return null;
    }
    $transactionId = 'VENDU-' . $reference . '-' . time();
    $ch = curl_init('https://api-checkout.cinetpay.com/v2/payment');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'apikey' => CINETPAY_API_KEY, 'site_id' => CINETPAY_SITE_ID, 'transaction_id' => $transactionId,
        'amount' => (int)$montant, 'currency' => 'XOF', 'description' => $description,
        'notify_url' => CINETPAY_URL_NOTIFICATION, 'return_url' => CINETPAY_URL_RETOUR, 'channels' => 'ALL',
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $reponse = curl_exec($ch);
    $erreur = curl_error($ch);
    curl_close($ch);
    if ($erreur) { error_log("[CinetPay] cURL : $erreur"); return null; }
    $resultat = json_decode($reponse, true);
    if (isset($resultat['code']) && $resultat['code'] == '201' && isset($resultat['data']['payment_url'])) {
        return ['url' => $resultat['data']['payment_url'], 'transaction_id' => $transactionId];
    }
    error_log("[CinetPay] Échec : $reponse");
    return null;
}

function verifierPaiementMobileMoney($transactionId) {
    if (!PAIEMENT_ACTIF) return false;
    $ch = curl_init('https://api-checkout.cinetpay.com/v2/payment/check');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'apikey' => CINETPAY_API_KEY, 'site_id' => CINETPAY_SITE_ID, 'transaction_id' => $transactionId,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $reponse = curl_exec($ch);
    curl_close($ch);
    $resultat = json_decode($reponse, true);
    return isset($resultat['data']['status']) && $resultat['data']['status'] === 'ACCEPTED';
}
