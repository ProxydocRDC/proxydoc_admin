# Guide des permissions Shield

Ce document explique l'importance des permissions personnalisées et le sens de chaque section du formulaire de gestion des rôles (Shield).

## Accès au formulaire

**Sécurité > Rôles** → Créer ou modifier un rôle → onglets **Permissions**.

---

## Onglets et messages d'aide

### 1. Ressources

**Contrôle l'accès aux listes, formulaires et actions des ressources** (Patients, Utilisateurs, Commandes, etc.). Chaque case active une action précise pour ce rôle.

| Permission | Signification |
|------------|---------------|
| `view_any` | Lister les enregistrements |
| `view` | Voir le détail d'un enregistrement |
| `create` | Créer un nouvel enregistrement |
| `update` | Modifier un enregistrement existant |
| `delete` | Supprimer un enregistrement |
| `delete_any` | Supprimer plusieurs enregistrements (lot) |

Exemples d'entités :
- **user** : Utilisateurs
- **user::patient** : Utilisateurs & Patients (fiche patient liée)
- **proxy::patient** : Patients

---

### 2. Pages

**Contrôle l'accès aux pages personnalisées.** Une page sans permission n'apparaît pas dans le menu.

Exemples : Dashboard, Suivi des expéditions, etc.

---

### 3. Widgets

**Contrôle la visibilité des widgets** (statistiques, graphiques) sur le tableau de bord. Sans permission, le widget est masqué.

Exemples :
- **widget_PatientStatsWidget** : Statistique « Patients ajoutés (3 derniers jours) »

---

### 4. Permissions personnalisées

**Permissions créées manuellement ou par des modules**, non liées aux ressources/widgets/pages standards. Utile pour des actions spécifiques ou des intégrations tierces.

Cet onglet affiche les permissions non générées automatiquement par Shield (migrations, seeders, modules tiers). Elles permettent de gérer des accès très spécifiques sans créer une ressource complète.

---

## Bonnes pratiques

1. **Principe du moindre privilège** : n'attribuez que les permissions nécessaires.
2. **Rôles super_admin et Admin** : bypassent les vérifications de permissions (accès total).
3. **Régénération des permissions** : après ajout de ressources/widgets/pages, exécutez :
   ```bash
   php artisan shield:generate --all --panel=admin
   ```

---

## Fichiers liés

| Fichier | Rôle |
|---------|------|
| `lang/vendor/filament-shield/fr/filament-shield.php` | Traductions et messages d'aide |
| `app/Filament/Resources/Shield/RoleResource.php` | Formulaire des rôles avec descriptions |
| `database/seeders/PatientPermissionsSeeder.php` | Permissions patient (proxy::patient, user::patient) |
