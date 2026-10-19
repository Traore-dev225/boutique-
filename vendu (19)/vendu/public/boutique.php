<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare("SELECT * FROM commercants WHERE slug = ?");
$stmt->execute([$_GET['slug'] ?? '']);
$commercant = $stmt->fetch();
if (!$commercant) { http_response_code(404); echo "Boutique introuvable."; exit; }
if ($commercant['bloque']) { http_response_code(403); echo "Cette boutique est actuellement indisponible."; exit; }

$stmt = $pdo->prepare("SELECT * FROM produits WHERE commercant_id = ? AND statut = 'actif' ORDER BY date_creation DESC");
$stmt->execute([$commercant['id']]);
$produits = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($commercant['nom_boutique']) ?> — Vendu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur"><a href="index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a></div></nav>
<header class="boutique-entete"><div class="conteneur">
    <h1><?= e($commercant['nom_boutique']) ?></h1>
    <?php if ($commercant['ville']): ?><p>📍 <?= e($commercant['ville']) ?></p><?php endif; ?>
</div></header>
<div class="conteneur">
<?php if (empty($produits)): ?>
    <div class="vide">Cette boutique n'a pas encore de produits en ligne.</div>
<?php else: ?>
    <div class="grille-produits">
    <?php foreach ($produits as $produit): ?>
        <a href="produit.php?id=<?= (int)$produit['id'] ?>" class="produit-carte">
            <?php if ($produit['photo_url']): ?><img src="../assets/uploads/<?= e($produit['photo_url']) ?>" alt="<?= e($produit['nom']) ?>"><?php else: ?><div class="placeholder-image">Pas de photo</div><?php endif; ?>
            <div class="produit-carte-corps">
                <?php if ($produit['categorie']): ?><span class="categorie"><?= e($produit['categorie']) ?></span><?php endif; ?>
                <h3><?= e($produit['nom']) ?></h3>
                <div class="prix"><?= formaterPrix($produit['prix']) ?></div>
            </div>
        </a>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/_pied.php'; ?>
</body>
</html>
