<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/auth.php';

exigerConnexion();
$commercant = commercantConnecte($pdo);
$id = $commercant['id'];

function compter($pdo, $sql, $id) { $s = $pdo->prepare($sql); $s->execute([$id]); return $s->fetchColumn(); }
$nbProduits = compter($pdo, "SELECT COUNT(*) FROM produits WHERE commercant_id = ?", $id);
$nbNouvelles = compter($pdo, "SELECT COUNT(*) FROM commandes WHERE commercant_id = ? AND statut = 'nouvelle'", $id);
$nbTotal = compter($pdo, "SELECT COUNT(*) FROM commandes WHERE commercant_id = ?", $id);
$totalVentes = compter($pdo, "SELECT COALESCE(SUM(montant_total), 0) FROM commandes WHERE commercant_id = ? AND statut = 'livree'", $id);

$stmt = $pdo->prepare("SELECT * FROM commandes WHERE commercant_id = ? ORDER BY date_creation DESC LIMIT 5");
$stmt->execute([$id]);
$dernieres = $stmt->fetchAll();
$labels = ['nouvelle' => 'Nouvelle', 'confirmee' => 'Confirmée', 'livree' => 'Livrée', 'annulee' => 'Annulée'];

$titrePage = 'Tableau de bord';
$pageActive = 'dashboard';
require __DIR__ . '/_entete.php';
?>
<div class="dash-entete">
    <h1>Bonjour, <?= e($commercant['nom_boutique']) ?> 👋</h1>
    <span class="badge badge-<?= e($commercant['plan']) ?>"><?= ucfirst(e($commercant['plan'])) ?></span>
</div>
<div class="stats-grille">
    <div class="stat-carte"><div class="valeur"><?= $nbProduits ?><?= $commercant['plan'] === 'gratuit' ? '/10' : '' ?></div><div class="libelle">Produits en ligne</div></div>
    <div class="stat-carte"><div class="valeur"><?= $nbNouvelles ?></div><div class="libelle">Nouvelles commandes</div></div>
    <div class="stat-carte"><div class="valeur"><?= $nbTotal ?></div><div class="libelle">Commandes au total</div></div>
    <div class="stat-carte"><div class="valeur"><?= formaterPrix($totalVentes) ?></div><div class="libelle">Ventes livrées</div></div>
</div>
<h3>Dernières commandes</h3>
<?php if (empty($dernieres)): ?>
    <div class="vide">Aucune commande pour l'instant. Partage le lien de ta boutique sur WhatsApp pour commencer à vendre !</div>
<?php else: ?>
    <table>
        <tr><th>Client</th><th>Téléphone</th><th>Montant</th><th>Statut</th><th>Date</th></tr>
        <?php foreach ($dernieres as $c): ?>
            <tr>
                <td><?= e($c['client_nom']) ?></td><td><?= e($c['client_telephone']) ?></td><td><?= formaterPrix($c['montant_total']) ?></td>
                <td><span class="badge badge-<?= e($c['statut']) ?>"><?= $labels[$c['statut']] ?></span></td>
                <td><?= date('d/m/Y', strtotime($c['date_creation'])) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/_pied.php'; ?>
