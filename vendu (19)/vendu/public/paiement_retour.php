<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/paiement.php';

$transactionId = $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? '';
$estAbonnement = strpos($transactionId, 'VENDU-abo-') === 0;
$paiementConfirme = false;
$type = '';

if ($estAbonnement) {
    $type = 'abonnement';
    $stmt = $pdo->prepare("SELECT * FROM abonnement_paiements WHERE transaction_id = ?");
    $stmt->execute([$transactionId]);
    $ap = $stmt->fetch();
    if ($ap) {
        $paiementConfirme = verifierPaiementMobileMoney($transactionId);
        if ($paiementConfirme && $ap['statut'] !== 'accepte') {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE abonnement_paiements SET statut = 'accepte' WHERE id = ?")->execute([$ap['id']]);
            $pdo->prepare("UPDATE commercants SET plan = ?, plan_date_fin = ? WHERE id = ?")->execute([$ap['plan'], date('Y-m-d', strtotime('+30 days')), $ap['commercant_id']]);
            $pdo->commit();
        } elseif (!$paiementConfirme && $ap['statut'] === 'en_attente') {
            $pdo->prepare("UPDATE abonnement_paiements SET statut = 'refuse' WHERE id = ?")->execute([$ap['id']]);
        }
    }
} else {
    $type = 'commande';
    $stmt = $pdo->prepare("SELECT * FROM transactions_paiement WHERE transaction_id = ?");
    $stmt->execute([$transactionId]);
    $t = $stmt->fetch();
    if ($t) {
        // On revérifie toujours auprès de CinetPay : jamais de confiance aveugle dans l'URL
        $paiementConfirme = verifierPaiementMobileMoney($transactionId);
        if ($paiementConfirme && $t['statut'] !== 'accepte') {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE transactions_paiement SET statut = 'accepte' WHERE id = ?")->execute([$t['id']]);
            $pdo->prepare("UPDATE commandes SET statut = 'confirmee' WHERE id = ?")->execute([$t['commande_id']]);
            $pdo->commit();
        } elseif (!$paiementConfirme && $t['statut'] === 'en_attente') {
            $pdo->prepare("UPDATE transactions_paiement SET statut = 'refuse' WHERE id = ?")->execute([$t['id']]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Statut du paiement — Vendu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur"><a href="index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a></div></nav>
<div class="conteneur"><div class="carte carte-etroite">
<?php if ($paiementConfirme && $type === 'abonnement'): ?>
    <div class="message message-succes">✅ Ton abonnement a bien été activé ! Il est valable 30 jours.</div>
<?php elseif ($paiementConfirme): ?>
    <div class="message message-succes">✅ Ton paiement a bien été reçu ! Ta commande est confirmée.</div>
<?php else: ?>
    <div class="message message-erreur">❌ Le paiement n'a pas pu être confirmé. Si le montant a été débité, contacte le commerçant directement — sinon, tu peux réessayer.</div>
<?php endif; ?>
<a href="index.php" class="btn btn-contour btn-bloc">Retour à l'accueil</a>
</div></div>
<?php require __DIR__ . '/_pied.php'; ?>
</body>
</html>
