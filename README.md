# 🌳 Menu Laravel Filament — Menu Tree

> **Fork amélioré de** [solutionforest/filament-tree](https://github.com/solutionforest/filament-tree)

> ℹ️ Ce projet conserve le copyright original de Solution Forest (MIT).
> Les modifications sont listées dans [CHANGELOG.md](./CHANGELOG.md).

[![CI](https://github.com/menu-laravel-filament/menu-tree/actions/workflows/ci.yml/badge.svg)](https://github.com/menu-laravel-filament/menu-tree/actions/workflows/ci.yml)

Un plugin **Laravel + Filament** pour la gestion d'arbres hiérarchiques avec glisser-déposer (drag & drop), barre de recherche, lazy loading, et support dark mode.

---

## ✨ Améliorations apportées (vs l'original)

| Fonctionnalité | Original | 👍 Menu Tree |
|---------------|----------|-------------|
| **Drag & Drop** | jQuery Nestable (obsolète) | ✅ **SortableJS** (Vanilla JS, moderne, maintenu) |
| **Dépendance jQuery** | Oui (~87KB) | ✅ **0 jQuery** — plus léger |
| **Barre de recherche** | ❌ Non | ✅ Recherche en direct avec surlignage |
| **Lazy Loading** | ❌ Tout l'arbre chargé d'un coup | ✅ Chargement à la demande (pour >500 nœuds) |
| **parent_id = -1** | Magic number obligatoire | ✅ **parent_id = NULL** (convention Laravel) |
| **Dark Mode** | Non optimisé | ✅ Styles dark mode natifs |
| **État vide** | Aucun | ✅ Empty state quand aucun élément |
| **Profondeur** | getDepth() indisponible | ✅ Calcul de profondeur récursif |
| **Configuration** | Limitée | ✅ Fichier de config enrichi |

---

## 🚀 Installation

```bash
composer require menu-laravel-filament/menu-tree
php artisan filament:assets
php artisan vendor:publish --tag="filament-tree-config"
```

### Base de données

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
    $table->integer('order')->default(0);
    $table->string('title');
    $table->timestamps();
});
```

### Modèle

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentTree\Concern\ModelTree;

class Category extends Model
{
    use ModelTree;

    protected $fillable = ['parent_id', 'title', 'order'];
}
```

### Générer les composants

```bash
# Widget (dans un dashboard)
php artisan make:filament-tree-widget CategoryWidget --model=Category

# Page standalone
php artisan make:filament-tree-page CategoryTree --model=Category

# Page dans une resource
php artisan make:filament-tree-page CategoryTree --resource=Category
```

---

## 🔍 Recherche

La barre de recherche est activée par défaut :

```php
// Pour désactiver :
// config/filament-tree.php
'search' => [
    'enabled' => false,
],
```

- Recherche insensible à la casse
- Survlignage des résultats en jaune
- Les parents des résultats sont automatiquement visibles
- Débounce de 300ms pour les performances

---

## ⚡ Lazy Loading

Pour les grands arbres (500+ nœuds), activez le lazy loading :

```php
// config/filament-tree.php
'lazy_loading' => [
    'enabled' => true,
    'load_on_expand' => true,
    'page_size' => 50,
],
```

Les enfants sont chargés uniquement quand l'utilisateur développe un nœud.

---

## 🎨 Personnalisation

```php
class CategoryWidget extends BaseWidget
{
    protected static string $model = Category::class;
    protected static int $maxDepth = 5;

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('title')->required(),
            Textarea::make('description'),
        ];
    }

    public function getTreeRecordTitle($record): string
    {
        if (! $record) return '';
        $count = $record->children()->count();
        return "{$record->title} (" . ($count ?: '0') . ")";
    }

    public function getTreeRecordIcon($record): ?string
    {
        return match (true) {
            $record->isRoot() => 'heroicon-o-folder',
            default => 'heroicon-o-document',
        };
    }
}
```

---

## 🧪 Tests

```bash
composer test
```

---

## 📄 License

MIT. Ce projet est un **fork** de [solutionforest/filament-tree](https://github.com/solutionforest/filament-tree)
(Licence MIT, copyright © Solution Forest).  
Les modifications et améliorations sont sous la même licence MIT.

```
Copyright (c) Solution Forest <info@solutionforest.net>
Copyright (c) 2026 — menu-laravel-filament (fork)
```

Cf. fichier [LICENSE.md](./LICENSE.md) pour le texte complet.
