<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/whatsapp.php';

$succes = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM commercants WHERE email = ?");
    $stmt->execute([trim($_POST['email'] ?? '')]);
    $commercant = $stmt->fetch();
    if ($commercant) {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE commercants SET reset_code = ?, reset_code_expire = ? WHERE id = ?")
            ->execute([$code, date('Y-m-d H:i:s', strtotime('+15 minutes')), $commercant['id']]);
        envoyerMessageWhatsApp($commercant['telephone'], "🔑 Ton code de réinitialisation Vendu est : $code\nIl expire dans 15 minutes. Si tu n'es pas à l'origine de cette demande, ignore ce message.");
    }
    $succes = true; // on ne révèle jamais si l'email existe
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mot de passe oublié — Vendu</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur"><a href="../public/index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a></div></nav>
<div class="conteneur"><div class="carte carte-etroite">
<?php if ($succes): ?>
    <div class="message message-succes">📱 Si un compte existe avec cet email, un code de réinitialisation vient d'être envoyé sur le numéro WhatsApp associé.</div>
    <a href="reinitialiser_mot_de_passe.php" class="btn btn-vert btn-bloc">J'ai reçu mon code</a>
<?php else: ?>
    <h2 style="margin-top:0;">Mot de passe oublié</h2>
    <p style="color:var(--gris); font-size:14px; margin-top:-8px;">Un code te sera envoyé par WhatsApp sur le numéro associé à ton compte.</p>
    <form method="POST">
        <div class="champ"><label>Email de ton compte</label><input type="email" name="email" required></div>
        <button type="submit" class="btn btn-vert btn-bloc btn-large">Recevoir mon code</button>
    </form>
<?php endif; ?>
<p style="text-align:center; margin-top:16px; font-size:14px;"><a href="login.php" style="color:var(--vert-fonce); font-weight:600;">Retour à la connexion</a></p>
</div></div>
</body>
</html>
