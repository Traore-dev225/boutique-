<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/whatsapp.php';
require __DIR__ . '/../includes/paiement.php';

$produitId = (int)($_GET['produit_id'] ?? $_POST['produit_id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT produits.*, commercants.nom_boutique, commercants.slug, commercants.telephone AS commercant_telephone
    FROM produits JOIN commercants ON commercants.id = produits.commercant_id
    WHERE produits.id = ? AND produits.statut = 'actif' AND commercants.bloque = 0");
$stmt->execute([$produitId]);
$produit = $stmt->fetch();
if (!$produit) { http_response_code(404); echo "Produit introuvable."; exit; }

// Règle : un commerçant (ou son employé) connecté ne peut pas commander ses propres produits.
// Vérifié côté serveur, donc impossible à contourner en modifiant la page.
if (estConnecte() && (int)$_SESSION['commercant_id'] === (int)$produit['commercant_id']) {
    http_response_code(403);
    $blocageAutoCommande = true;
} else {
    $blocageAutoCommande = false;
}

$erreur = '';
$succes = false;

if (!$blocageAutoCommande && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['client_nom'] ?? '');
    $telephone = trim($_POST['client_telephone'] ?? '');
    $adresse = trim($_POST['client_adresse'] ?? '');
    $quantite = max(1, (int)($_POST['quantite'] ?? 1));

    if ($nom === '' || $telephone === '') {
        $erreur = "Merci de renseigner ton nom et ton numéro de téléphone.";
    } else {
        $montantTotal = $produit['prix'] * $quantite;
        $modePaiement = ($_POST['mode_paiement'] ?? 'a_la_livraison') === 'mobile_money' ? 'mobile_money' : 'a_la_livraison';

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO commandes (commercant_id, client_nom, client_telephone, client_adresse, statut, montant_total, mode_paiement) VALUES (?, ?, ?, ?, 'nouvelle', ?, ?)");
            $stmt->execute([$produit['commercant_id'], $nom, $telephone, $adresse, $montantTotal, $modePaiement]);
            $commandeId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO commande_lignes (commande_id, produit_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)");
            $stmt->execute([$commandeId, $produit['id'], $quantite, $produit['prix']]);
            $pdo->commit();

            notifierNouvelleCommande(['telephone' => $produit['commercant_telephone']], [
                'client_nom' => $nom, 'client_telephone' => $telephone, 'montant_total' => $montantTotal,
            ]);

            if ($modePaiement === 'mobile_money') {
                $resultat = initierPaiementMobileMoney($commandeId, $montantTotal, "Commande #$commandeId - " . $produit['nom']);
                if ($resultat) {
                    $stmt = $pdo->prepare("INSERT INTO transactions_paiement (commande_id, transaction_id, montant, statut) VALUES (?, ?, ?, 'en_attente')");
                    $stmt->execute([$commandeId, $resultat['transaction_id'], $montantTotal]);
                    redirigerVers($resultat['url']);
                } else {
                    $succes = true; // paiement en ligne non configuré : à régler à la livraison
                }
            } else {
                $succes = true;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erreur = "Une erreur est survenue, merci de réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Commander <?= e($produit['nom']) ?> — Vendu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur">
    <a href="index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a>
    <a href="produit.php?id=<?= (int)$produit['id'] ?>" class="btn btn-contour btn-petit">← Retour au produit</a>
</div></nav>

<div class="conteneur"><div class="carte carte-etroite">
<?php if ($blocageAutoCommande): ?>
    <div class="message message-erreur">Tu ne peux pas commander tes propres produits.</div>
    <a href="../admin/produits.php" class="btn btn-contour btn-bloc">Gérer mes produits</a>
<?php elseif ($succes): ?>
    <div class="message message-succes">✅ Ta commande a bien été envoyée à <strong><?= e($produit['nom_boutique']) ?></strong> ! Le commerçant va te contacter au numéro que tu as fourni.</div>
    <a href="<?= e(telephoneVersLienWhatsApp($produit['commercant_telephone'], 'Bonjour, je viens de commander "' . $produit['nom'] . '" sur Vendu.')) ?>" target="_blank" class="btn btn-vert btn-bloc">💬 Confirmer directement sur WhatsApp</a>
<?php else: ?>
    <h2 style="margin-top:0;">Commander : <?= e($produit['nom']) ?></h2>
    <p style="color:var(--gris); margin-top:-8px;">Prix unitaire : <?= formaterPrix($produit['prix']) ?></p>
    <?php if ($erreur): ?><div class="message message-erreur"><?= e($erreur) ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="produit_id" value="<?= (int)$produit['id'] ?>">
        <div class="champ"><label>Ton nom</label><input type="text" name="client_nom" value="<?= e($_POST['client_nom'] ?? '') ?>" required></div>
        <div class="champ"><label>Ton numéro de téléphone</label><input type="tel" name="client_telephone" value="<?= e($_POST['client_telephone'] ?? '') ?>" required></div>
        <div class="champ"><label>Adresse de livraison (optionnel)</label><input type="text" name="client_adresse" value="<?= e($_POST['client_adresse'] ?? '') ?>"></div>
        <div class="champ"><label>Quantité</label><input type="number" name="quantite" min="1" value="<?= e($_POST['quantite'] ?? '1') ?>" required></div>
        <div class="champ"><label>Mode de paiement</label>
            <select name="mode_paiement">
                <option value="a_la_livraison">Paiement à la livraison</option>
                <option value="mobile_money">Payer maintenant (Orange Money, MTN, Wave...)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-vert btn-bloc btn-large">Envoyer ma commande</button>
    </form>
<?php endif; ?>
</div></div>
<?php require __DIR__ . '/_pied.php'; ?>
</body>
</html>
