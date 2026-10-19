<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/whatsapp.php';

exigerConnexion();
$commercant = commercantConnecte($pdo);

if (($_GET['action'] ?? '') === 'statut' && isset($_GET['id'], $_GET['vers'])) {
    if (in_array($_GET['vers'], ['nouvelle','confirmee','livree','annulee'], true)) {
        $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ? AND commercant_id = ?")->execute([$_GET['vers'], (int)$_GET['id'], $commercant['id']]);
        $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id = ? AND commercant_id = ?");
        $stmt->execute([(int)$_GET['id'], $commercant['id']]);
        if ($c = $stmt->fetch()) notifierChangementStatutCommande($c, $commercant['nom_boutique']);
    }
    redirigerVers('commandes.php');
}

$filtre = $_GET['statut'] ?? 'toutes';
$sql = "SELECT commandes.*, GROUP_CONCAT(produits.nom SEPARATOR ', ') AS produits_noms FROM commandes
        LEFT JOIN commande_lignes ON commande_lignes.commande_id = commandes.id
        LEFT JOIN produits ON produits.id = commande_lignes.produit_id
        WHERE commandes.commercant_id = ?";
$params = [$commercant['id']];
if ($filtre !== 'toutes') { $sql .= " AND commandes.statut = ?"; $params[] = $filtre; }
$sql .= " GROUP BY commandes.id ORDER BY commandes.date_creation DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();
$labels = ['nouvelle' => 'Nouvelle', 'confirmee' => 'Confirmée', 'livree' => 'Livrée', 'annulee' => 'Annulée'];

$titrePage = 'Commandes';
$pageActive = 'commandes';
require __DIR__ . '/_entete.php';
?>
<div class="dash-entete"><h1>Commandes</h1></div>
<div style="margin-bottom:20px; display:flex; gap:8px; flex-wrap:wrap;">
    <?php foreach (['toutes' => 'Toutes', 'nouvelle' => 'Nouvelles', 'confirmee' => 'Confirmées', 'livree' => 'Livrées', 'annulee' => 'Annulées'] as $k => $l): ?>
        <a href="commandes.php?statut=<?= $k ?>" class="btn <?= $filtre === $k ? 'btn-vert' : 'btn-contour' ?> btn-petit"><?= $l ?></a>
    <?php endforeach; ?>
</div>
<?php if (empty($commandes)): ?>
    <div class="vide">Aucune commande dans cette catégorie.</div>
<?php else: ?>
    <table>
        <tr><th>Client</th><th>Téléphone</th><th>Produit(s)</th><th>Montant</th><th>Paiement</th><th>Statut</th><th>Date</th><th>Actions</th></tr>
        <?php foreach ($commandes as $c): ?>
            <tr>
                <td><?= e($c['client_nom']) ?></td>
                <td><a href="<?= e(telephoneVersLienWhatsApp($c['client_telephone'], 'Bonjour ' . $c['client_nom'] . ', concernant votre commande sur Vendu...')) ?>" target="_blank">💬 <?= e($c['client_telephone']) ?></a></td>
                <td><?= e($c['produits_noms'] ?: '—') ?></td>
                <td><?= formaterPrix($c['montant_total']) ?></td>
                <td><?= $c['mode_paiement'] === 'mobile_money' ? '<span class="badge badge-livree">💳 Payé en ligne</span>' : '<span class="badge badge-gratuit">À la livraison</span>' ?></td>
                <td><span class="badge badge-<?= e($c['statut']) ?>"><?= $labels[$c['statut']] ?></span></td>
                <td><?= date('d/m/Y H:i', strtotime($c['date_creation'])) ?></td>
                <td>
                    <select onchange="if(this.value) window.location.href=this.value" style="padding:6px; border-radius:6px; border:1px solid var(--bordure); font-size:13px;">
                        <option value="">Changer le statut...</option>
                        <option value="commandes.php?action=statut&id=<?= (int)$c['id'] ?>&vers=confirmee">Confirmée</option>
                        <option value="commandes.php?action=statut&id=<?= (int)$c['id'] ?>&vers=livree">Livrée</option>
                        <option value="commandes.php?action=statut&id=<?= (int)$c['id'] ?>&vers=annulee">Annulée</option>
                    </select>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/_pied.php'; ?>
