<?php
require __DIR__ . '/../../includes/db.php';
require __DIR__ . '/../../includes/fonctions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['super_admin'])) { header('Location: login.php'); exit; }

$action = $_GET['action'] ?? '';
if ($action === 'traiter' && isset($_GET['id'])) { $pdo->prepare("UPDATE signalements SET traite = 1 WHERE id = ?")->execute([(int)$_GET['id']]); header('Location: signalements.php'); exit; }
if ($action === 'bloquer' && isset($_GET['commercant_id'])) { $pdo->prepare("UPDATE commercants SET bloque = 1 WHERE id = ?")->execute([(int)$_GET['commercant_id']]); header('Location: signalements.php'); exit; }
if ($action === 'debloquer' && isset($_GET['commercant_id'])) { $pdo->prepare("UPDATE commercants SET bloque = 0 WHERE id = ?")->execute([(int)$_GET['commercant_id']]); header('Location: signalements.php'); exit; }

$signalements = $pdo->query("
    SELECT signalements.*, produits.nom AS produit_nom, produits.id AS produit_id, commercants.id AS commercant_id, commercants.nom_boutique, commercants.bloque
    FROM signalements JOIN produits ON produits.id = signalements.produit_id JOIN commercants ON commercants.id = produits.commercant_id
    ORDER BY signalements.traite ASC, signalements.date_creation DESC")->fetchAll();
$motifs = ['produit_interdit' => 'Produit interdit', 'arnaque' => "Suspicion d'arnaque", 'contenu_trompeur' => 'Contenu trompeur', 'autre' => 'Autre'];
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Modération — Vendu</title><link rel="stylesheet" href="../../assets/css/style.css"></head>
<body>
<nav class="navbar"><div class="conteneur"><a href="../../public/index.php" class="logo"><img src="../../assets/img/logo.png" alt="Vendu" class="logo-image"></a> — Modération</div></nav>
<div class="conteneur" style="padding:30px 20px;">
    <h1>Signalements</h1>
    <?php if (empty($signalements)): ?><div class="vide">Aucun signalement pour l'instant.</div>
    <?php else: ?><table>
        <tr><th>Produit</th><th>Boutique</th><th>Motif</th><th>Détails</th><th>Statut</th><th>Actions</th></tr>
        <?php foreach ($signalements as $s): ?><tr>
            <td><a href="../../public/produit.php?id=<?= (int)$s['produit_id'] ?>" target="_blank"><?= e($s['produit_nom']) ?></a></td>
            <td><?= e($s['nom_boutique']) ?> <?= $s['bloque'] ? '<span class="badge badge-annulee">Bloquée</span>' : '' ?></td>
            <td><?= e($motifs[$s['motif']] ?? $s['motif']) ?></td><td><?= e($s['commentaire'] ?: '—') ?></td>
            <td><span class="badge <?= $s['traite'] ? 'badge-livree' : 'badge-nouvelle' ?>"><?= $s['traite'] ? 'Traité' : 'Nouveau' ?></span></td>
            <td class="tableau-actions">
                <?php if (!$s['traite']): ?><a href="signalements.php?action=traiter&id=<?= (int)$s['id'] ?>" class="btn btn-contour btn-petit">Marquer traité</a><?php endif; ?>
                <?php if ($s['bloque']): ?><a href="signalements.php?action=debloquer&commercant_id=<?= (int)$s['commercant_id'] ?>" class="btn btn-contour btn-petit">Débloquer boutique</a>
                <?php else: ?><a href="signalements.php?action=bloquer&commercant_id=<?= (int)$s['commercant_id'] ?>" class="btn btn-danger btn-petit" onclick="return confirm('Bloquer cette boutique ?');">Bloquer boutique</a><?php endif; ?>
            </td></tr><?php endforeach; ?>
    </table><?php endif; ?>
</div>
</body>
</html>
