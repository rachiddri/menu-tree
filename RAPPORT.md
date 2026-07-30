# Rapport détaillé du projet : menu-laravel-filament/menu-tree

> **Date :** 30 juillet 2026  
> **Package :** `menu-laravel-filament/menu-tree`  
> **Fork de :** `solutionforest/filament-tree`  
> **Licence :** MIT  

---

## 1. Objectif

Plugin **Filament** (Laravel) qui fournit un **arbre hiérarchique glissable** (drag-and-drop) pour gérer des catégories, menus, ou toute entité avec une structure parent-enfant.

L'objectif de cette version améliorée est de remplacer l'ancienne dépendance **jQuery Nestable** (jQuery 87 KB, déprécié) par **SortableJS** (6 KB, Vanilla JS, maintenu) et d'ajouter des fonctionnalités modernes.

---

## 2. Architecture

```
menu-laravel-filament/
├── bin/
│   └── build.js                    # Builder esbuild pour le JS
├── config/
│   └── filament-tree.php           # Configuration du plugin
├── resources/
│   ├── css/
│   │   ├── button.css              # Styles des boutons
│   │   ├── custom-nestable-item.css # Styles des items de l'arbre
│   │   └── plugin.css              # Styles principaux (Tailwind)
│   ├── dist/                       # Fichiers compilés
│   │   ├── components/
│   │   │   └── filament-tree-component.js  # 41 KB (SortableJS inclus)
│   │   ├── filament-tree.js                # 41 KB (entrée Alpine)
│   │   └── filament-tree.css               # CSS compilé
│   ├── js/
│   │   ├── components/
│   │   │   └── filament-tree-component.js  # Composant Alpine (source)
│   │   └── filament-tree.js                # Point d'entrée Alpine
│   ├── lang/                       # 20 langues
│   └── views/
│       ├── actions/                # Templates des actions (boutons)
│       ├── components/
│       │   ├── actions/            # Actions Filament
│       │   ├── forms/tree/         # Formulaire arbre
│       │   ├── modal/              # Modale
│       │   └── tree/               # Templates de l'arbre
│       │       ├── index.blade.php  # Conteneur principal
│       │       ├── item.blade.php   # Item de l'arbre
│       │       ├── item-display.blade.php  # Affichage item
│       │       └── list.blade.php   # Liste d'items
│       ├── forms/                  # Formulaire arbre
│       ├── pages/                  # Page arbre
│       └── widgets/                # Widget arbre
└── src/
    ├── Actions/                    # Actions : Create, Edit, Delete, View
    ├── Commands/                   # Commandes artisan make:
    │   ├── FileGenerators/         # Générateurs de classes
    │   ├── MakeTreePageCommand.php
    │   └── MakeTreeWidgetCommand.php
    ├── Components/
    │   └── Tree.php                # Composant Tree (le cœur)
    ├── Concern/
    │   ├── Actions/                # Traits pour les actions
    │   ├── HasActions.php          # Gestion des actions
    │   ├── HasEmptyState.php       # État vide
    │   ├── HasHeading.php          # Titre
    │   ├── HasRecords.php          # Gestion des enregistrements
    │   ├── HasTranslatableRecords.php  # Support traduction
    │   ├── InteractWithTree.php    # Mise à jour/sérialisation
    │   ├── ModelTree.php           # Trait pour les modèles
    │   └── SupportTranslation.php  # Traduction
    ├── Contract/
    │   └── HasTree.php             # Interface
    ├── FilamentTreeServiceProvider.php  # Service Provider
    ├── Forms/Components/
    │   └── Tree.php                # Composant formulaire
    ├── Macros/
    │   └── BlueprintMarcos.php     # Macros pour migrations
    ├── Pages/
    │   └── TreePage.php            # Page arbre
    ├── Resources/Pages/
    │   └── TreePage.php            # Page ressource arbre
    ├── Support/
    │   └── Utils.php               # Utilitaires
    └── Widgets/
        ├── BaseWidget.php          # Widget de base
        └── Tree.php                # Widget arbre
```

---

## 3. Changements par rapport à l'original

### 3.1. Dépendances

| Avant (solutionforest) | Après (menu-laravel-filament) |
|------------------------|-------------------------------|
| `jquery` (87 KB) | ❌ Supprimé |
| `jquery.nestable.js` | ❌ Supprimé |
| `custom.nestable.js` | ❌ Supprimé |
| `sortablejs: ^1.15.0` | ✅ Ajouté (6 KB) |
| `esbuild` | ✅ Conservé |

### 3.2. JavaScript

| Fichier | Description |
|---------|-------------|
| `resources/js/filament-tree.js` | Point d'entrée — enregistre le composant Alpine |
| `resources/js/components/filament-tree-component.js` | Composant Alpine complet avec SortableJS |

**Fonctionnalités JS :**
- ✅ **SortableJS** pour le drag-and-drop (plus de jQuery)
- ✅ **Barre de recherche** avec surlignage en direct
- ✅ **Expand/Collapse** tous
- ✅ **Lazy loading** pour les grands arbres
- ✅ **Sauvegarde** via Livewire
- ✅ **Validation de profondeur** (maxDepth)
- ✅ **Gestion des erreurs** et loading states

### 3.3. PHP / Backend

| Fichier | Changements |
|---------|-------------|
| `src/Support/Utils.php` | `defaultParentId()` → retourne `null` au lieu de `-1` |
| | Ajout de `isRootKey()`, `filterTreeBySearch()`, `highlightSearchTerm()` |
| `src/Concern/ModelTree.php` | `isRoot()` → utilise `null` parent_id |
| | Ajout de `getDepth()`, `scopeIsRoot()` |
| `src/Concern/HasRecords.php` | `getRootLayerRecords()` → supporte `null` parent_id |
| | Ajout de `getRecordsQuery()` |
| `src/Concern/InteractWithTree.php` | Ajout de `loadTreeChildren()` pour lazy loading |
| `src/FilamentTreeServiceProvider.php` | Assets mis à jour (plus de jQuery) |

### 3.4. Configuration

**`config/filament-tree.php` :**
```php
'default_parent_id' => null,    // Au lieu de -1
'search' => [
    'enabled' => true,
    'placeholder' => 'Search tree...',
],
'lazy_loading' => [
    'enabled' => false,
    'threshold' => 50,
],
'max_depth' => 0,               // 0 = illimité
```

### 3.5. Blade / Templates

- **`index.blade.php`** : Ajout de la barre de recherche, overlay de chargement, état vide
- **`item.blade.php`** : Restructuré pour SortableJS (handle, no-drag zones)
- **`x-ignore`** sur le conteneur de l'arbre pour éviter les conflits Alpine/SortableJS

### 3.6. CSS

- Styles pour la barre de recherche (mode clair/sombre)
- Styles pour le clone de drag (compact, sans icônes superflus)
- `cursor: grab` sur la poignée
- `touch-action: none` pour le tactile
- État vide stylisé

---

## 4. Flux de fonctionnement

### 4.1. Initialisation

```
1. Page charge → filament-tree.js s'exécute
2. Alpine.data('treeNestableComponent', fn) enregistre le composant
3. Blade rend le template avec x-data="treeNestableComponent({...})"
4. Alpine initialise le composant → init() est appelé
5. init() trouve le conteneur par ID → initSortable()
6. SortableJS est initialisé sur chaque <ol class="dd-list">
7. La barre de recherche et les boutons sont gérés par Alpine
```

### 4.2. Drag-and-drop

```
1. Utilisateur saisit la poignée (⋮⋮)
2. SortableJS capture le mousedown/touchstart
3. Un clone de l'élément suit le curseur (stylisé compact)
4. L'élément peut être déposé dans la même liste ou une liste enfant
5. Au relâchement, onEnd() → serialize() → updateTree() via Livewire
6. Les parent_id et order sont mis à jour en base de données
```

### 4.3. Sauvegarde

```
JS: serialize() → [{id: 1, children: [{id: 5}]}, {id: 2}]
↓
Livewire: updateTree($list)
↓
PHP: unnestArray() → aplatit la structure
↓
PHP: Pour chaque élément, met à jour parent_id et order
↓
PHP: Si changement, save() → needReload = true
↓
JS: $refresh() si besoin
```

---

## 5. Utilisation

### 5.1. Installation

```bash
composer require menu-laravel-filament/menu-tree
php artisan filament:assets
php artisan vendor:publish --tag="filament-tree-config"
```

### 5.2. Créer un modèle d'arbre

```php
use SolutionForest\FilamentTree\Concern\ModelTree;

class Category extends Model
{
    use ModelTree;

    protected $fillable = ['parent_id', 'title', 'order'];
}
```

### 5.3. Migration

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_id')->nullable()
          ->constrained('categories')->cascadeOnDelete();
    $table->integer('order')->default(0);
    $table->string('title');
    $table->timestamps();
});
```

### 5.4. Créer un widget

```bash
php artisan make:filament-tree-widget CategoryWidget --model=Category
```

---

## 6. Test d'acceptation

Un projet de test a été créé dans `test-app/` avec :

- **Laravel 11** + **Filament 5**
- **19 catégories** hiérarchisées sur 3 niveaux
- Modèle `Category` avec `parent_id` nullable
- Widget `CategoryWidget` avec barre de recherche, maxDepth=5
- Seeder avec catégories de piscine (Piscines → Chlore → Chlore Choc...)

**Identifiants :** `admin@test.com` / `password`

**URL :** `http://localhost:8000/admin`

---

## 7. Problèmes résolus

| Problème | Cause | Solution |
|----------|-------|----------|
| Drag & drop ne fonctionnait pas | `button` dans le `filter` SortableJS | Retiré `button` du filter |
| Drag & drop ne fonctionnait pas | Conflit Alpine/SortableJS (DOM) | Ajout de `x-ignore` sur le conteneur |
| Doublons d'instances SortableJS | Pas de destroy avant réinit | `destroySortable()` avant chaque init |
| Icône géante pendant le drag | Clone gardait tous les styles | CSS pour `.dd-drag` : compact + cache boutons |
| `parent_id = -1` (magic number) | Valeur par défaut de l'original | Changé pour `null` partout |
| jQuery 87 KB | Dépendance lourde | Remplacé par SortableJS 6 KB |

---

## 8. Limitations connues

1. **Nesting** : SortableJS ne gère pas le nesting horizontal automatique comme jQuery Nestable. Pour imbriquer, la liste enfant doit être déployée (expand) avant de déposer.

2. **Build CSS** : Le build Tailwind CSS ne fonctionne pas sur Termux/Android (lightningcss non compatible ARM). Les styles sont ajoutés manuellement au fichier dist.

3. **Test utilisateur** : Impossible de tester le login via curl (Livewire/Alpine côté client). Nécessite un navigateur.

---

## 9. Statistiques

| Métrique | Valeur |
|----------|--------|
| Fichiers PHP source | 40 |
| Fichiers JS | 2 |
| Fichiers CSS | 3 |
| Templates Blade | 21 |
| Langues supportées | 20 |
| Taille JS (minifié) | 41 KB par fichier |
| Taille CSS (minifié) | 16 KB |
| Dépendances npm | SortableJS, esbuild |
| Dépendances Composer | Filament 5+, Laravel |

---

## 10. Roadmap

- [x] Remplacer jQuery Nestable par SortableJS
- [x] Ajouter la barre de recherche
- [x] Ajouter le lazy loading
- [x] `parent_id = NULL` au lieu de `-1`
- [x] Support dark mode
- [x] État vide
- [x] Calcul de profondeur
- [ ] Tests unitaires automatisés
- [ ] CI/CD pipeline
- [ ] Publication sur Packagist
- [ ] Documentation API complète

---

*Rapport généré le 30 juillet 2026 — Projet `menu-laravel-filament/menu-tree`*
