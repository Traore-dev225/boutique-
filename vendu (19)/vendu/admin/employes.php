<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/auth.php';

exigerProprietaire();
$commercant = commercantConnecte($pdo);
$titrePage = 'Comptes employés';
$pageActive = 'employes';

if ($commercant['plan'] !== 'business') {
    require __DIR__ . '/_entete.php'; ?>
    <div class="dash-entete"><h1>Comptes employés</h1></div>
    <div class="carte" style="text-align:center; padding:50px 20px;">
        <p style="font-size:16px; margin:0 0 16px;">Les comptes employés sont réservés à l'offre <strong>Business</strong>.</p>
        <a href="abonnement.php" class="btn btn-vert btn-large">Passer à Business</a>
    </div>
    <?php require __DIR__ . '/_pied.php'; exit;
}

$erreur = '';
if (($_GET['action'] ?? '') === 'supprimer' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM commercant_employes WHERE id = ? AND commercant_id = ?")->execute([(int)$_GET['id'], $commercant['id']]);
    redirigerVers('employes.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? ''); $email = trim($_POST['email'] ?? ''); $mdp = $_POST['mot_de_passe'] ?? '';
    if ($nom === '' || $email === '' || strlen($mdp) < 6) {
        $erreur = "Merci de renseigner un nom, un email valide et un mot de passe d'au moins 6 caractères.";
    } else {
        $s = $pdo->prepare("SELECT (SELECT COUNT(*) FROM commercant_employes WHERE email = ?) + (SELECT COUNT(*) FROM commercants WHERE email = ?)");
        $s->execute([$email, $email]);
        if ($s->fetchColumn() > 0) {
            $erreur = "Cet email est déjà utilisé.";
        } else {
            $pdo->prepare("INSERT INTO commercant_employes (commercant_id, nom, email, mot_de_passe_hash) VALUES (?, ?, ?, ?)")
                ->execute([$commercant['id'], $nom, $email, password_hash($mdp, PASSWORD_DEFAULT)]);
            redirigerVers('employes.php');
        }
    }
}
$stmt = $pdo->prepare("SELECT * FROM commercant_employes WHERE commercant_id = ? ORDER BY date_creation DESC");
$stmt->execute([$commercant['id']]);
$employes = $stmt->fetchAll();
require __DIR__ . '/_entete.php';
?>
<div class="dash-entete"><h1>Comptes employés</h1><span class="badge badge-business">Business</span></div>
<?php if ($erreur): ?><div class="message message-erreur"><?= e($erreur) ?></div><?php endif; ?>
<div class="carte" style="margin-bottom:30px;">
    <h3 style="margin-top:0;">Ajouter un employé</h3>
    <p style="color:var(--gris); font-size:13px; margin-top:-8px;">Un employé peut gérer les produits et les commandes, mais pas l'abonnement ni les comptes employés.</p>
    <form method="POST">
        <div class="champ"><label>Nom</label><input type="text" name="nom" required></div>
        <div class="champ"><label>Email</label><input type="email" name="email" required></div>
        <div class="champ"><label>Mot de passe</label><input type="password" name="mot_de_passe" required></div>
        <button type="submit" class="btn btn-vert btn-large">Ajouter l'employé</button>
    </form>
</div>
<?php if (empty($employes)): ?><div class="vide">Aucun employé pour l'instant.</div>
<?php else: ?><table><tr><th>Nom</th><th>Email</th><th>Actions</th></tr>
    <?php foreach ($employes as $em): ?><tr><td><?= e($em['nom']) ?></td><td><?= e($em['email']) ?></td>
        <td><a href="employes.php?action=supprimer&id=<?= (int)$em['id'] ?>" class="btn btn-danger btn-petit" onclick="return confirm('Supprimer cet employé ?');">Supprimer</a></td></tr><?php endforeach; ?>
</table><?php endif; ?>
<?php require __DIR__ . '/_pied.php'; ?>
