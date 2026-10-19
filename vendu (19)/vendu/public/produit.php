<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT produits.*, commercants.nom_boutique, commercants.slug, commercants.telephone
    FROM produits JOIN commercants ON commercants.id = produits.commercant_id
    WHERE produits.id = ? AND produits.statut = 'actif' AND commercants.bloque = 0");
$stmt->execute([$id]);
$produit = $stmt->fetch();
if (!$produit) { http_response_code(404); echo "Produit introuvable."; exit; }

// Un commerçant (ou son employé) connecté ne peut pas commander ses propres produits
$estSonProduit = estConnecte() && (int)$_SESSION['commercant_id'] === (int)$produit['commercant_id'];

$lienBoutique = 'boutique.php?slug=' . urlencode($produit['slug']);
$lienProduitAbsolu = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$messageWhatsApp = "Bonjour, je suis intéressé(e) par \"" . $produit['nom'] . "\" (" . formaterPrix($produit['prix']) . ") vu sur votre boutique Vendu.";
$lienWhatsApp = telephoneVersLienWhatsApp($produit['telephone'], $messageWhatsApp);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($produit['nom']) ?> — <?= e($produit['nom_boutique']) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur">
    <a href="index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a>
    <a href="<?= $lienBoutique ?>" class="btn btn-contour btn-petit">← Retour à la boutique</a>
</div></nav>

<div class="conteneur"><div class="produit-detail">
    <div>
        <?php if ($produit['photo_url']): ?><img src="../assets/uploads/<?= e($produit['photo_url']) ?>" alt="<?= e($produit['nom']) ?>"><?php else: ?><div class="placeholder-image">Pas de photo</div><?php endif; ?>
    </div>
    <div>
        <?php if ($produit['categorie']): ?><span class="categorie"><?= e($produit['categorie']) ?></span><?php endif; ?>
        <h1><?= e($produit['nom']) ?></h1>
        <div class="prix"><?= formaterPrix($produit['prix']) ?></div>
        <p class="description"><?= nl2br(e($produit['description'])) ?></p>

        <?php if ($estSonProduit): ?>
            <div class="message message-erreur" style="margin-bottom:10px;">
                Ceci est un de tes propres produits : tu ne peux pas le commander.
            </div>
            <a href="../admin/produits.php" class="btn btn-contour btn-bloc" style="margin-bottom:10px;">Gérer mes produits</a>
        <?php else: ?>
            <a href="commander.php?produit_id=<?= (int)$produit['id'] ?>" class="btn btn-vert btn-large btn-bloc" style="margin-bottom:10px;">Commander ce produit</a>
            <a href="<?= e($lienWhatsApp) ?>" target="_blank" class="btn btn-contour btn-bloc" style="margin-bottom:10px;">💬 Discuter sur WhatsApp</a>
        <?php endif; ?>

        <button type="button" class="btn btn-contour btn-bloc" onclick="partagerSurTikTok()">🎵 Copier le lien pour TikTok</button>
        <p id="confirmation-copie" style="display:none; color:var(--vert-fonce); font-size:13px; text-align:center; margin-top:8px;">✅ Lien copié ! Colle-le dans ta bio ou ta légende TikTok.</p>
        <div style="text-align:center; margin-top:16px;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?= urlencode($lienProduitAbsolu) ?>" alt="QR code du produit" style="border:1px solid var(--bordure); border-radius:8px; padding:6px; background:white;">
            <p style="font-size:12px; color:var(--gris); margin-top:6px;">Affiche ce QR code pendant tes vidéos ou lives</p>
        </div>
        <p style="text-align:center; margin-top:16px;"><a href="signaler.php?produit_id=<?= (int)$produit['id'] ?>" style="color:var(--gris); font-size:12px;">🚩 Signaler ce produit</a></p>
        <script>
        function partagerSurTikTok() {
            const texte = <?= json_encode($produit['nom'] . ' - ' . formaterPrix($produit['prix']) . "\n" . $lienProduitAbsolu) ?>;
            navigator.clipboard.writeText(texte).then(function() { document.getElementById('confirmation-copie').style.display = 'block'; });
        }
        </script>
    </div>
</div></div>
<?php require __DIR__ . '/_pied.php'; ?>
</body>
</html>
