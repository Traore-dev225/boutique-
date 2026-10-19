<?php
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/fonctions.php';

$recherche = trim($_GET['q'] ?? '');
$categorie = trim($_GET['categorie'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$parPage = 12;
$offset = ($page - 1) * $parPage;

$conditions = ["produits.statut = 'actif'", "commercants.bloque = 0"];
$params = [];
if ($recherche !== '') { $conditions[] = "(produits.nom LIKE ? OR produits.description LIKE ?)"; $params[] = "%$recherche%"; $params[] = "%$recherche%"; }
if ($categorie !== '') { $conditions[] = "produits.categorie = ?"; $params[] = $categorie; }
$whereSql = implode(' AND ', $conditions);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM produits JOIN commercants ON commercants.id = produits.commercant_id WHERE $whereSql");
$stmt->execute($params);
$totalPages = max(1, ceil($stmt->fetchColumn() / $parPage));

$stmt = $pdo->prepare("
    SELECT produits.*, commercants.nom_boutique, commercants.slug, commercants.plan
    FROM produits JOIN commercants ON commercants.id = produits.commercant_id
    WHERE $whereSql
    ORDER BY CASE commercants.plan WHEN 'business' THEN 0 WHEN 'pro' THEN 1 ELSE 2 END, produits.date_creation DESC
    LIMIT $parPage OFFSET $offset");
$stmt->execute($params);
$produits = $stmt->fetchAll();

$categoriesDisponibles = $pdo->query("SELECT DISTINCT categorie FROM produits WHERE categorie IS NOT NULL AND categorie != '' ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);

$produitsCarrousel = $pdo->query("
    SELECT produits.id, produits.nom, produits.prix, produits.photo_url, commercants.nom_boutique
    FROM produits JOIN commercants ON commercants.id = produits.commercant_id
    WHERE produits.statut = 'actif' AND commercants.bloque = 0 AND produits.photo_url IS NOT NULL AND produits.photo_url != ''
    ORDER BY CASE commercants.plan WHEN 'business' THEN 0 WHEN 'pro' THEN 1 ELSE 2 END, produits.date_creation DESC
    LIMIT 6")->fetchAll();

function urlAvecParametre($cle, $valeur) {
    $params = $_GET;
    if ($valeur === null || $valeur === '') unset($params[$cle]); else $params[$cle] = $valeur;
    return 'index.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vendu — Ta mini-boutique WhatsApp</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar"><div class="conteneur">
    <a href="index.php" class="logo"><img src="../assets/img/logo.png" alt="Vendu" class="logo-image"></a>
    <div class="nav-liens">
        <a href="decouvrir.php" class="btn btn-contour btn-petit">Découvrir les boutiques</a>
        <a href="../admin/login.php" class="btn btn-contour btn-petit">Connexion</a>
        <a href="../admin/inscription.php" class="btn btn-vert btn-petit">Créer ma boutique</a>
    </div>
</div></nav>

<header class="hero"><div class="conteneur">
    <h1>Vends sur WhatsApp<br>comme un vrai commerce.</h1>
    <p>Crée ta mini-boutique en ligne, partage le lien sur WhatsApp et suis tes commandes et ton argent, le tout depuis un seul tableau de bord.</p>
    <a href="../admin/inscription.php" class="btn btn-vert btn-large">Démarrer gratuitement</a>
</div></header>

<?php if (!empty($produitsCarrousel)): ?>
<div class="conteneur" style="padding-top:20px;">
    <div class="carrousel"><div class="carrousel-piste" id="carrousel-piste">
        <?php foreach ($produitsCarrousel as $p): ?>
            <a href="produit.php?id=<?= (int)$p['id'] ?>" class="carrousel-diapo">
                <img src="../assets/uploads/<?= e($p['photo_url']) ?>" alt="<?= e($p['nom']) ?>">
                <div class="carrousel-legende"><strong><?= e($p['nom']) ?></strong><span><?= e($p['nom_boutique']) ?> · <?= formaterPrix($p['prix']) ?></span></div>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="carrousel-puces">
        <?php foreach ($produitsCarrousel as $i => $p): ?><span class="carrousel-puce <?= $i === 0 ? 'actif' : '' ?>" data-index="<?= $i ?>"></span><?php endforeach; ?>
    </div></div>
</div>
<?php endif; ?>

<div class="conteneur">
    <h2 style="margin:20px 0 4px;">Tous les produits</h2>
    <p style="color:var(--gris); margin:0 0 16px;">Découverts sur les boutiques Vendu</p>
    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;">
        <input type="text" name="q" placeholder="Rechercher un produit..." value="<?= e($recherche) ?>" style="flex:1; min-width:200px; padding:10px 12px; border:1px solid var(--bordure); border-radius:8px;">
        <select name="categorie" style="padding:10px 12px; border:1px solid var(--bordure); border-radius:8px;">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categoriesDisponibles as $cat): ?><option value="<?= e($cat) ?>" <?= $categorie === $cat ? 'selected' : '' ?>><?= e($cat) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-vert">Filtrer</button>
        <?php if ($recherche !== '' || $categorie !== ''): ?><a href="index.php" class="btn btn-contour">Réinitialiser</a><?php endif; ?>
    </form>

    <?php if (empty($produits)): ?>
        <div class="vide">Aucun produit ne correspond à ta recherche.</div>
    <?php else: ?>
        <div class="grille-produits">
            <?php foreach ($produits as $produit): ?>
                <a href="produit.php?id=<?= (int)$produit['id'] ?>" class="produit-carte">
                    <?php if ($produit['photo_url']): ?><img src="../assets/uploads/<?= e($produit['photo_url']) ?>" alt="<?= e($produit['nom']) ?>"><?php else: ?><div class="placeholder-image">Pas de photo</div><?php endif; ?>
                    <div class="produit-carte-corps">
                        <?php if ($produit['plan'] !== 'gratuit'): ?><span class="categorie" style="background:#f3e8ff; color:#7c3aed;">✨ <?= e($produit['nom_boutique']) ?></span><?php else: ?><span class="categorie"><?= e($produit['nom_boutique']) ?></span><?php endif; ?>
                        <h3><?= e($produit['nom']) ?></h3>
                        <div class="prix"><?= formaterPrix($produit['prix']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <div style="display:flex; gap:8px; justify-content:center; padding:20px 0 60px; flex-wrap:wrap;">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?><a href="<?= e(urlAvecParametre('page', $i)) ?>" class="btn <?= $i === $page ? 'btn-vert' : 'btn-contour' ?> btn-petit"><?= $i ?></a><?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<section class="conteneur"><div class="fonctionnalites">
    <div class="fonctionnalite-carte"><div class="icone">🛍️</div><h3>Mini-boutique personnalisée</h3><p>Une page rien qu'à toi avec tous tes produits, prête à partager.</p></div>
    <div class="fonctionnalite-carte"><div class="icone">📱</div><h3>Lien partageable WhatsApp</h3><p>Envoie tes fiches produits directement dans tes discussions.</p></div>
    <div class="fonctionnalite-carte"><div class="icone">📦</div><h3>Gestion des commandes</h3><p>Suis chaque commande du statut "nouvelle" jusqu'à "livrée".</p></div>
    <div class="fonctionnalite-carte"><div class="icone">💰</div><h3>Suivi des ventes</h3><p>Un tableau de bord clair pour voir ce que tu as vendu et gagné.</p></div>
</div></section>

<?php require __DIR__ . '/_pied.php'; ?>

<?php if (!empty($produitsCarrousel)): ?>
<script>
(function() {
    const piste = document.getElementById('carrousel-piste');
    const puces = document.querySelectorAll('.carrousel-puce');
    let indexActuel = 0; const total = <?= count($produitsCarrousel) ?>;
    function aller(i) { indexActuel = i; piste.style.transform = 'translateX(-' + (i * 100) + '%)'; puces.forEach((p, k) => p.classList.toggle('actif', k === i)); }
    puces.forEach(p => p.addEventListener('click', () => aller(parseInt(p.dataset.index))));
    setInterval(() => aller((indexActuel + 1) % total), 4000);
})();
</script>
<?php endif; ?>
</body>
</html>
