<?php
// Accès super-admin (propriétaire du site). CHANGE ce mot de passe avant la mise en ligne !
define('SUPER_ADMIN_MOT_DE_PASSE', 'change-moi-avant-la-mise-en-ligne');
if (session_status() === PHP_SESSION_NONE) session_start();
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hash_equals(SUPER_ADMIN_MOT_DE_PASSE, $_POST['mot_de_passe'] ?? '')) {
        $_SESSION['super_admin'] = true;
        header('Location: signalements.php'); exit;
    }
    $erreur = "Mot de passe incorrect.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Administration — Vendu</title><link rel="stylesheet" href="../../assets/css/style.css"></head>
<body><div class="conteneur"><div class="carte carte-etroite">
    <h2 style="margin-top:0;">Accès administrateur</h2>
    <?php if ($erreur): ?><div class="message message-erreur"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
    <form method="POST">
        <div class="champ"><label>Mot de passe maître</label><input type="password" name="mot_de_passe" required></div>
        <button type="submit" class="btn btn-vert btn-bloc btn-large">Entrer</button>
    </form>
</div></div></body>
</html>
