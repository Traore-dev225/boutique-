<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/auth.php';

if (estConnecte()) redirigerVers('dashboard.php');
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomBoutique = trim($_POST['nom_boutique'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';

    if ($nomBoutique === '' || $email === '' || $telephone === '' || $mdp === '') {
        $erreur = "Merci de remplir tous les champs obligatoires.";
    } elseif (strlen($mdp) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM commercants WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $erreur = "Un compte existe déjà avec cet email.";
        } else {
            $slug = slugUnique($pdo, genererSlug($nomBoutique));
            $stmt = $pdo->prepare("INSERT INTO commercants (nom_boutique, slug, email, mot_de_passe_hash, telephone, ville, plan) VALUES (?, ?, ?, ?, ?, ?, 'gratuit')");
            $stmt->execute([$nomBoutique, $slug, $email, password_hash($mdp, PASSWORD_DEFAULT), $telephone, $ville]);
            connecter($pdo->lastInsertId());
            redirigerVers('dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Créer ma boutique — Vendu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur"><a href="../public/index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a></div></nav>
<div class="conteneur"><div class="carte carte-etroite">
    <h2 style="margin-top:0;">Crée ta boutique</h2>
    <?php if ($erreur): ?><div class="message message-erreur"><?= e($erreur) ?></div><?php endif; ?>
    <form method="POST">
        <div class="champ"><label>Nom de la boutique</label><input type="text" name="nom_boutique" value="<?= e($_POST['nom_boutique'] ?? '') ?>" required></div>
        <div class="champ"><label>Email</label><input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></div>
        <div class="champ"><label>Numéro de téléphone (WhatsApp)</label><input type="tel" name="telephone" value="<?= e($_POST['telephone'] ?? '') ?>" required></div>
        <div class="champ"><label>Ville</label><input type="text" name="ville" value="<?= e($_POST['ville'] ?? '') ?>"></div>
        <div class="champ"><label>Mot de passe</label><input type="password" name="mot_de_passe" required></div>
        <button type="submit" class="btn btn-vert btn-bloc btn-large">Créer ma boutique</button>
    </form>
    <p style="text-align:center; margin-top:16px; font-size:14px;">Déjà inscrit ? <a href="login.php" style="color:var(--vert-fonce); font-weight:600;">Connecte-toi</a></p>
</div></div>
</body>
</html>
