<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';

$erreur = '';
$succes = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $nouveau = $_POST['nouveau_mot_de_passe'] ?? '';
    if (strlen($nouveau) < 6) {
        $erreur = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM commercants WHERE email = ? AND reset_code = ?");
        $stmt->execute([$email, $code]);
        $c = $stmt->fetch();
        if (!$c || strtotime($c['reset_code_expire']) < time()) {
            $erreur = "Code invalide ou expiré. Merci de refaire une demande.";
        } else {
            $pdo->prepare("UPDATE commercants SET mot_de_passe_hash = ?, reset_code = NULL, reset_code_expire = NULL WHERE id = ?")
                ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $c['id']]);
            $succes = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Réinitialiser le mot de passe — Vendu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur"><a href="../public/index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a></div></nav>
<div class="conteneur"><div class="carte carte-etroite">
<?php if ($succes): ?>
    <div class="message message-succes">✅ Ton mot de passe a été réinitialisé avec succès.</div>
    <a href="login.php" class="btn btn-vert btn-bloc">Se connecter</a>
<?php else: ?>
    <h2 style="margin-top:0;">Nouveau mot de passe</h2>
    <?php if ($erreur): ?><div class="message message-erreur"><?= e($erreur) ?></div><?php endif; ?>
    <form method="POST">
        <div class="champ"><label>Email</label><input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></div>
        <div class="champ"><label>Code reçu par WhatsApp</label><input type="text" name="code" maxlength="6" required></div>
        <div class="champ"><label>Nouveau mot de passe</label><input type="password" name="nouveau_mot_de_passe" required></div>
        <button type="submit" class="btn btn-vert btn-bloc btn-large">Réinitialiser</button>
    </form>
<?php endif; ?>
</div></div>
</body>
</html>
