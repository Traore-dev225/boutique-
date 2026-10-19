<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';

$boutiques = $pdo->query("
    SELECT commercants.*, COUNT(produits.id) AS nb_produits
    FROM commercants
    LEFT JOIN produits ON produits.commercant_id = commercants.id AND produits.statut = 'actif'
    WHERE commercants.bloque = 0
    GROUP BY commercants.id
    HAVING nb_produits > 0
    ORDER BY CASE commercants.plan WHEN 'business' THEN 0 WHEN 'pro' THEN 1 ELSE 2 END, commercants.date_creation DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Découvrir les boutiques — Vendu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur"><a href="index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a></div></nav>
<header class="boutique-entete"><div class="conteneur"><h1>Découvrir les boutiques</h1><p>Toutes les boutiques actives sur Vendu</p></div></header>
<div class="conteneur">
<?php if (empty($boutiques)): ?>
    <div class="vide">Aucune boutique active pour l'instant.</div>
<?php else: ?>
    <div class="grille-produits">
    <?php foreach ($boutiques as $b): ?>
        <a href="boutique.php?slug=<?= urlencode($b['slug']) ?>" class="produit-carte">
            <div class="placeholder-image" style="font-size:34px;">🏪</div>
            <div class="produit-carte-corps">
                <?php if ($b['plan'] !== 'gratuit'): ?><span class="categorie" style="background:#f3e8ff; color:#7c3aed;">✨ <?= $b['plan'] === 'business' ? 'Boutique Business' : 'Boutique Pro' ?></span><?php endif; ?>
                <h3><?= e($b['nom_boutique']) ?></h3>
                <p style="color:var(--gris); font-size:13px; margin:0 0 8px;"><?= e($b['ville'] ?: '—') ?></p>
                <div class="prix"><?= (int)$b['nb_produits'] ?> produit<?= $b['nb_produits'] > 1 ? 's' : '' ?></div>
            </div>
        </a>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>
<?php require __DIR__ . '/_pied.php'; ?>
</body>
</html>
