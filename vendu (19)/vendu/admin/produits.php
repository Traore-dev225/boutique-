<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/ai.php';

exigerConnexion();
$commercant = commercantConnecte($pdo);
$erreur = '';
$action = $_GET['action'] ?? 'liste';
$produitEnEdition = null;

if ($action === 'supprimer' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM produits WHERE id = ? AND commercant_id = ?")->execute([(int)$_GET['id'], $commercant['id']]);
    redirigerVers('produits.php');
}
if ($action === 'editer' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ? AND commercant_id = ?");
    $stmt->execute([(int)$_GET['id'], $commercant['id']]);
    $produitEnEdition = $stmt->fetch();
    if (!$produitEnEdition) redirigerVers('produits.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = (float)($_POST['prix'] ?? 0);
    $categorie = trim($_POST['categorie'] ?? '');
    $statut = ($_POST['statut'] ?? 'actif') === 'inactif' ? 'inactif' : 'actif';
    $produitId = (int)($_POST['produit_id'] ?? 0);

    if ($nom === '' || $prix <= 0) {
        $erreur = "Merci de renseigner au minimum le nom et un prix valide.";
    } elseif ($produitId === 0 && limiteProduitsAtteinte($pdo, $commercant['id'], $commercant['plan'])) {
        $erreur = "Tu as atteint la limite de 10 produits de l'offre Gratuite. Passe à l'offre Pro pour en ajouter davantage.";
    } else {
        $photo = traiterUploadPhoto($_FILES['photo'] ?? null);
        if ($produitId > 0) {
            if ($photo) {
                $pdo->prepare("UPDATE produits SET nom=?, description=?, prix=?, categorie=?, statut=?, photo_url=? WHERE id=? AND commercant_id=?")
                    ->execute([$nom, $description, $prix, $categorie, $statut, $photo, $produitId, $commercant['id']]);
            } else {
                $pdo->prepare("UPDATE produits SET nom=?, description=?, prix=?, categorie=?, statut=? WHERE id=? AND commercant_id=?")
                    ->execute([$nom, $description, $prix, $categorie, $statut, $produitId, $commercant['id']]);
            }
        } else {
            $pdo->prepare("INSERT INTO produits (commercant_id, nom, description, prix, categorie, photo_url, statut) VALUES (?,?,?,?,?,?,?)")
                ->execute([$commercant['id'], $nom, $description, $prix, $categorie, $photo, $statut]);
        }
        redirigerVers('produits.php');
    }
}

$stmt = $pdo->prepare("SELECT * FROM produits WHERE commercant_id = ? ORDER BY date_creation DESC");
$stmt->execute([$commercant['id']]);
$produits = $stmt->fetchAll();

$titrePage = 'Mes produits';
$pageActive = 'produits';
require __DIR__ . '/_entete.php';
?>
<div class="dash-entete"><h1>Mes produits</h1></div>
<?php if ($erreur): ?><div class="message message-erreur"><?= e($erreur) ?></div><?php endif; ?>

<div class="carte" style="margin-bottom:30px;">
    <h3 style="margin-top:0;"><?= $produitEnEdition ? 'Modifier le produit' : 'Ajouter un produit' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="produit_id" value="<?= $produitEnEdition ? (int)$produitEnEdition['id'] : 0 ?>">
        <div class="champ"><label>Nom du produit</label><input type="text" name="nom" id="champ-nom" value="<?= e($produitEnEdition['nom'] ?? '') ?>" required></div>
        <div class="champ"><label>Description</label><textarea name="description" id="champ-description"><?= e($produitEnEdition['description'] ?? '') ?></textarea></div>
        <div class="champ"><label>Prix (FCFA)</label><input type="number" step="1" min="1" name="prix" value="<?= e($produitEnEdition['prix'] ?? '') ?>" required></div>
        <div class="champ"><label>Catégorie</label><input type="text" name="categorie" id="champ-categorie" value="<?= e($produitEnEdition['categorie'] ?? '') ?>" placeholder="Ex : Vêtements, Chaussures, Cosmétiques..."></div>
        <div class="champ"><label>Photo</label><input type="file" name="photo" id="champ-photo" accept="image/*">
            <?php if (!empty($produitEnEdition['photo_url'])): ?><small style="color:var(--gris);">Photo actuelle conservée si tu n'en choisis pas de nouvelle.</small><?php endif; ?>
        </div>
        <?php if (IA_ACTIVE): ?>
            <button type="button" id="btn-generer-ia" class="btn btn-contour" style="display:none; margin-bottom:16px;" onclick="genererFicheAvecIA()">🤖 Générer la fiche avec l'IA</button>
            <p id="ia-statut" style="display:none; font-size:13px; color:var(--gris); margin:-10px 0 16px;"></p>
        <?php endif; ?>
        <div class="champ"><label>Statut</label>
            <select name="statut">
                <option value="actif" <?= (($produitEnEdition['statut'] ?? 'actif') === 'actif') ? 'selected' : '' ?>>Actif (visible sur la boutique)</option>
                <option value="inactif" <?= (($produitEnEdition['statut'] ?? '') === 'inactif') ? 'selected' : '' ?>>Inactif (masqué)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-vert btn-large"><?= $produitEnEdition ? 'Enregistrer les modifications' : 'Ajouter le produit' ?></button>
        <?php if ($produitEnEdition): ?><a href="produits.php" class="btn btn-contour btn-large">Annuler</a><?php endif; ?>
    </form>
</div>

<?php if (empty($produits)): ?>
    <div class="vide">Tu n'as pas encore de produit. Ajoute ton premier produit ci-dessus 👆</div>
<?php else: ?>
    <table>
        <tr><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Statut</th><th>Actions</th></tr>
        <?php foreach ($produits as $p): ?>
            <tr>
                <td><?= e($p['nom']) ?></td><td><?= e($p['categorie'] ?: '—') ?></td><td><?= formaterPrix($p['prix']) ?></td>
                <td><span class="badge <?= $p['statut'] === 'actif' ? 'badge-livree' : 'badge-annulee' ?>"><?= $p['statut'] === 'actif' ? 'Actif' : 'Inactif' ?></span></td>
                <td class="tableau-actions">
                    <a href="produits.php?action=editer&id=<?= (int)$p['id'] ?>" class="btn btn-contour btn-petit">Modifier</a>
                    <a href="produits.php?action=supprimer&id=<?= (int)$p['id'] ?>" class="btn btn-danger btn-petit" onclick="return confirm('Supprimer ce produit ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<?php require __DIR__ . '/_pied.php'; ?>

<?php if (IA_ACTIVE): ?>
<script>
document.getElementById('champ-photo').addEventListener('change', function() {
    document.getElementById('btn-generer-ia').style.display = this.files.length ? 'inline-block' : 'none';
});
function genererFicheAvecIA() {
    const champ = document.getElementById('champ-photo'), bouton = document.getElementById('btn-generer-ia'), statut = document.getElementById('ia-statut');
    if (!champ.files.length) return;
    const fd = new FormData(); fd.append('photo', champ.files[0]);
    bouton.disabled = true; bouton.textContent = '⏳ Analyse en cours...'; statut.style.display = 'block'; statut.textContent = '';
    fetch('generer_fiche.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
        bouton.disabled = false; bouton.textContent = "🤖 Générer la fiche avec l'IA";
        if (data.erreur) { statut.textContent = '⚠️ ' + data.erreur; statut.style.color = 'var(--danger)'; return; }
        document.getElementById('champ-nom').value = data.fiche.nom;
        document.getElementById('champ-description').value = data.fiche.description;
        document.getElementById('champ-categorie').value = data.fiche.categorie;
        statut.textContent = "✅ Fiche générée ! Relis et ajuste si besoin avant d'enregistrer."; statut.style.color = 'var(--vert-fonce)';
    }).catch(() => { bouton.disabled = false; bouton.textContent = "🤖 Générer la fiche avec l'IA"; statut.textContent = '⚠️ Une erreur est survenue.'; statut.style.color = 'var(--danger)'; });
}
</script>
<?php endif; ?>
