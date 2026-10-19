<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';

$produitId = (int)($_GET['produit_id'] ?? $_POST['produit_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
$stmt->execute([$produitId]);
$produit = $stmt->fetch();
if (!$produit) { http_response_code(404); echo "Produit introuvable."; exit; }

$succes = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motif = trim($_POST['motif'] ?? '');
    if ($motif !== '') {
        $stmt = $pdo->prepare("INSERT INTO signalements (produit_id, motif, commentaire) VALUES (?, ?, ?)");
        $stmt->execute([$produitId, $motif, trim($_POST['commentaire'] ?? '')]);
        $succes = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Signaler un produit — Vendu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur"><a href="index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a></div></nav>
<div class="conteneur"><div class="carte carte-etroite">
<?php if ($succes): ?>
    <div class="message message-succes">✅ Merci, ton signalement a bien été transmis pour vérification.</div>
    <a href="produit.php?id=<?= (int)$produitId ?>" class="btn btn-contour btn-bloc">Retour au produit</a>
<?php else: ?>
    <h2 style="margin-top:0;">Signaler : <?= e($produit['nom']) ?></h2>
    <form method="POST">
        <input type="hidden" name="produit_id" value="<?= (int)$produitId ?>">
        <div class="champ"><label>Motif</label>
            <select name="motif" required>
                <option value="">Choisir un motif</option>
                <option value="produit_interdit">Produit interdit ou illégal</option>
                <option value="arnaque">Suspicion d'arnaque</option>
                <option value="contenu_trompeur">Description ou photo trompeuse</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div class="champ"><label>Détails (optionnel)</label><textarea name="commentaire"></textarea></div>
        <button type="submit" class="btn btn-danger btn-bloc btn-large">Envoyer le signalement</button>
    </form>
<?php endif; ?>
</div></div>
<?php require __DIR__ . '/_pied.php'; ?>
</body>
</html>
