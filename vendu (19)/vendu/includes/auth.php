<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function estConnecte() { return isset($_SESSION['commercant_id']); }
function estEmploye() { return isset($_SESSION['employe_id']); }

function exigerConnexion() {
    if (!estConnecte()) { header('Location: login.php'); exit; }
}
function exigerProprietaire() {
    exigerConnexion();
    if (estEmploye()) { header('Location: dashboard.php'); exit; }
}
function commercantConnecte($pdo) {
    if (!estConnecte()) return null;
    $stmt = $pdo->prepare("SELECT * FROM commercants WHERE id = ?");
    $stmt->execute([$_SESSION['commercant_id']]);
    $commercant = $stmt->fetch();
    if ($commercant && function_exists('verifierExpirationAbonnement')) {
        $commercant = verifierExpirationAbonnement($pdo, $commercant);
    }
    return $commercant;
}
function connecter($commercantId) {
    session_regenerate_id(true);
    $_SESSION = ['commercant_id' => $commercantId];
}
function connecterEmploye($commercantId, $employeId) {
    session_regenerate_id(true);
    $_SESSION = ['commercant_id' => $commercantId, 'employe_id' => $employeId];
}
function deconnecter() { $_SESSION = []; session_destroy(); }
