<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/auth.php';

exigerConnexion();
$commercant = commercantConnecte($pdo);
$titrePage = 'Statistiques avancées';
$pageActive = 'statistiques';

if ($commercant['plan'] !== 'business') {
    require __DIR__ . '/_entete.php'; ?>
    <div class="dash-entete"><h1>Statistiques avancées</h1></div>
    <div class="carte" style="text-align:center; padding:50px 20px;">
        <p style="font-size:16px; margin:0 0 16px;">Les statistiques avancées sont réservées à l'offre <strong>Business</strong>.</p>
        <a href="abonnement.php" class="btn btn-vert btn-large">Passer à Business</a>
    </div>
    <?php require __DIR__ . '/_pied.php'; exit;
}

$stmt = $pdo->prepare("SELECT DATE(date_creation) AS jour, COALESCE(SUM(montant_total), 0) AS total FROM commandes WHERE commercant_id = ? AND statut = 'livree' AND date_creation >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(date_creation)");
$stmt->execute([$commercant['id']]);
$brut = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$ventes = [];
for ($i = 6; $i >= 0; $i--) { $j = date('Y-m-d', strtotime("-$i days")); $ventes[$j] = $brut[$j] ?? 0; }
$max = max(1, max($ventes));

$stmt = $pdo->prepare("SELECT produits.nom, SUM(commande_lignes.quantite) AS qte, SUM(commande_lignes.quantite * commande_lignes.prix_unitaire) AS total
    FROM commande_lignes JOIN produits ON produits.id = commande_lignes.produit_id JOIN commandes ON commandes.id = commande_lignes.commande_id
    WHERE commandes.commercant_id = ? GROUP BY produits.id ORDER BY qte DESC LIMIT 5");
$stmt->execute([$commercant['id']]);
$populaires = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COALESCE(AVG(montant_total), 0) FROM commandes WHERE commercant_id = ? AND statut != 'annulee'");
$stmt->execute([$commercant['id']]);
$panier = $stmt->fetchColumn();

require __DIR__ . '/_entete.php';
?>
<div class="dash-entete"><h1>Statistiques avancées</h1><span class="badge badge-business">Business</span></div>
<div class="stats-grille"><div class="stat-carte"><div class="valeur"><?= formaterPrix($panier) ?></div><div class="libelle">Panier moyen</div></div></div>
<h3>Ventes livrées — 7 derniers jours</h3>
<div class="carte" style="margin-bottom:30px;"><div style="display:flex; align-items:flex-end; gap:10px; height:160px;">
    <?php foreach ($ventes as $jour => $m): ?>
        <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%;">
            <div style="font-size:11px; color:var(--gris); margin-bottom:4px;"><?= $m > 0 ? number_format($m, 0, ',', ' ') : '' ?></div>
            <div style="width:100%; background:var(--vert); border-radius:4px 4px 0 0; height:<?= max(4, ($m / $max) * 100) ?>%;"></div>
            <div style="font-size:11px; color:var(--gris); margin-top:6px;"><?= date('d/m', strtotime($jour)) ?></div>
        </div>
    <?php endforeach; ?>
</div></div>
<h3>Produits les plus vendus</h3>
<?php if (empty($populaires)): ?><div class="vide">Pas encore assez de ventes pour établir un classement.</div>
<?php else: ?><table><tr><th>Produit</th><th>Quantité vendue</th><th>Total généré</th></tr>
    <?php foreach ($populaires as $p): ?><tr><td><?= e($p['nom']) ?></td><td><?= (int)$p['qte'] ?></td><td><?= formaterPrix($p['total']) ?></td></tr><?php endforeach; ?>
</table><?php endif; ?>
<?php require __DIR__ . '/_pied.php'; ?>
