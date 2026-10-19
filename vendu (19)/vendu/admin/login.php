<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/auth.php';

if (estConnecte()) redirigerVers('dashboard.php');
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mot_de_passe'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM commercants WHERE email = ?");
    $stmt->execute([$email]);
    $commercant = $stmt->fetch();

    if ($commercant && password_verify($mdp, $commercant['mot_de_passe_hash'])) {
        if ($commercant['bloque']) {
            $erreur = "Ce compte a été suspendu. Contacte le support pour plus d'informations.";
        } else {
            connecter($commercant['id']);
            redirigerVers('dashboard.php');
        }
    } else {
        $stmt = $pdo->prepare("SELECT * FROM commercant_employes WHERE email = ?");
        $stmt->execute([$email]);
        $employe = $stmt->fetch();
        if ($employe && password_verify($mdp, $employe['mot_de_passe_hash'])) {
            connecterEmploye($employe['commercant_id'], $employe['id']);
            redirigerVers('dashboard.php');
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — Vendu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur"><a href="../public/index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a></div></nav>
<div class="conteneur"><div class="carte carte-etroite">
    <h2 style="margin-top:0;">Connexion</h2>
    <?php if ($erreur): ?><div class="message message-erreur"><?= e($erreur) ?></div><?php endif; ?>
    <form method="POST">
        <div class="champ"><label>Email</label><input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></div>
        <div class="champ"><label>Mot de passe</label><input type="password" name="mot_de_passe" required></div>
        <button type="submit" class="btn btn-vert btn-bloc btn-large">Se connecter</button>
    </form>
    <p style="text-align:center; margin-top:16px; font-size:14px;">
        Pas encore de boutique ? <a href="inscription.php" style="color:var(--vert-fonce); font-weight:600;">Crée-la maintenant</a><br>
        <a href="mot_de_passe_oublie.php" style="color:var(--gris); font-size:13px;">Mot de passe oublié ?</a>
    </p>
</div></div>
</body>
</html>
