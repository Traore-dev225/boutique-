<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($titrePage) ? e($titrePage) . ' — ' : '' ?>Vendu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dash-layout">
    <aside class="dash-sidebar">
        <a href="../public/index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a>
        <a href="dashboard.php" class="dash-lien <?= $pageActive === 'dashboard' ? 'actif' : '' ?>">📊 Tableau de bord</a>
        <a href="produits.php" class="dash-lien <?= $pageActive === 'produits' ? 'actif' : '' ?>">🛍️ Mes produits</a>
        <a href="commandes.php" class="dash-lien <?= $pageActive === 'commandes' ? 'actif' : '' ?>">📦 Commandes</a>
        <a href="statistiques.php" class="dash-lien <?= $pageActive === 'statistiques' ? 'actif' : '' ?>">📈 Statistiques <?= $commercant['plan'] !== 'business' ? '🔒' : '' ?></a>
        <?php if (!estEmploye()): ?>
            <a href="employes.php" class="dash-lien <?= $pageActive === 'employes' ? 'actif' : '' ?>">👥 Employés <?= $commercant['plan'] !== 'business' ? '🔒' : '' ?></a>
            <a href="abonnement.php" class="dash-lien <?= $pageActive === 'abonnement' ? 'actif' : '' ?>">⭐ Mon abonnement</a>
        <?php endif; ?>
        <a href="../public/boutique.php?slug=<?= urlencode($commercant['slug']) ?>" target="_blank" class="dash-lien">🔗 Voir ma boutique</a>
        <a href="deconnexion.php" class="dash-lien">🚪 Déconnexion</a>
    </aside>
    <main class="dash-contenu">
