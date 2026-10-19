<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/paiement.php';

exigerProprietaire();
$commercant = commercantConnecte($pdo);
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = $_POST['plan'] ?? '';
    if (!isset(PRIX_PLANS[$plan])) {
        $erreur = "Offre invalide.";
    } else {
        $montant = PRIX_PLANS[$plan];
        $resultat = initierPaiementMobileMoney('abo-' . $commercant['id'], $montant, "Abonnement $plan - " . $commercant['nom_boutique']);
        if ($resultat) {
            $pdo->prepare("INSERT INTO abonnement_paiements (commercant_id, plan, montant, transaction_id, statut) VALUES (?, ?, ?, ?, 'en_attente')")
                ->execute([$commercant['id'], $plan, $montant, $resultat['transaction_id']]);
            redirigerVers($resultat['url']);
        } else {
            $erreur = "Le paiement en ligne n'est pas encore configuré. Renseigne tes identifiants CinetPay dans includes/paiement.php pour activer les abonnements payants.";
        }
    }
}
$titrePage = 'Mon abonnement';
$pageActive = 'abonnement';
require __DIR__ . '/_entete.php';
?>
<div class="dash-entete"><h1>Mon abonnement</h1></div>
<?php if ($erreur): ?><div class="message message-erreur"><?= e($erreur) ?></div><?php endif; ?>
<div class="carte" style="margin-bottom:20px;">
    <p style="margin:0;">Offre actuelle : <span class="badge badge-<?= e($commercant['plan']) ?>"><?= ucfirst(e($commercant['plan'])) ?></span></p>
    <?php if ($commercant['plan'] !== 'gratuit' && $commercant['plan_date_fin']): ?>
        <p style="color:var(--gris); font-size:14px; margin:8px 0 0;">Renouvellement nécessaire avant le <?= date('d/m/Y', strtotime($commercant['plan_date_fin'])) ?>.</p>
    <?php endif; ?>
</div>
<div class="fonctionnalites">
    <div class="fonctionnalite-carte"><h3>Gratuit</h3><p>10 produits, fonctions de base.</p><p style="font-weight:700; margin:12px 0;">0 FCFA</p></div>
    <div class="fonctionnalite-carte"><h3>Pro</h3><p>Produits illimités + boutique mise en avant dans l'annuaire public.</p>
        <p style="font-weight:700; margin:12px 0;"><?= formaterPrix(PRIX_PLANS['pro']) ?> / mois</p>
        <?php if ($commercant['plan'] !== 'pro'): ?><form method="POST"><input type="hidden" name="plan" value="pro"><button type="submit" class="btn btn-vert btn-bloc">Passer à Pro</button></form><?php endif; ?>
    </div>
    <div class="fonctionnalite-carte"><h3>Business</h3><p>Tout Pro + statistiques avancées + comptes employés.</p>
        <p style="font-weight:700; margin:12px 0;"><?= formaterPrix(PRIX_PLANS['business']) ?> / mois</p>
        <?php if ($commercant['plan'] !== 'business'): ?><form method="POST"><input type="hidden" name="plan" value="business"><button type="submit" class="btn btn-vert btn-bloc">Passer à Business</button></form><?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/_pied.php'; ?>
