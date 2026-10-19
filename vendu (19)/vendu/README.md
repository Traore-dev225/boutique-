# Vendu — Mini-commerce WhatsApp pour petits commerçants (PHP / MySQL)

## Installation locale
1. Importe `sql/vendu.sql` dans MySQL (base existante ? utilise `sql/mise_a_jour.sql`).
2. Renseigne tes identifiants MySQL dans `includes/db.php`.
3. Depuis le dossier `vendu` (celui qui contient public, admin, includes, assets) :
   `php -S localhost:8000` puis ouvre http://localhost:8000/public/index.php
4. Le dossier `assets/uploads` doit être accessible en écriture.

## Offres
- Gratuit : 10 produits max
- Pro : produits illimités + mise en avant (accueil, annuaire, carrousel)
- Business : tout Pro + statistiques avancées + comptes employés

## Règles métier
- Un commerçant (ou son employé) connecté ne peut PAS commander ses propres produits
  (bouton masqué sur la fiche produit + blocage côté serveur dans `public/commander.php`).
- Une boutique bloquée (modération) disparaît de l'accueil, de l'annuaire, du carrousel,
  et ses fiches produit / commandes directes sont refusées.

## Activations externes (désactivées par défaut)
- WhatsApp (Twilio) : `includes/whatsapp.php` → `WHATSAPP_ACTIF`, SID, token
- Paiement Mobile Money (CinetPay) : `includes/paiement.php` → `PAIEMENT_ACTIF`, clé, site_id, URLs
- IA photo → fiche (Claude) : `includes/ai.php` → `IA_ACTIVE`, clé API (le bouton n'apparaît que si activée)
- Ne mets jamais tes clés dans un chat ni dans un dépôt public.

## Sécurité avant mise en ligne
- Change `SUPER_ADMIN_MOT_DE_PASSE` dans `admin/super/login.php` (accès : `admin/super/login.php`).
- Utilise HTTPS et un hébergement fiable (les offres gratuites ne conviennent pas à un vrai lancement).
