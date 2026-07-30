<?php

namespace SolutionForest\FilamentTree\Support;

use Illuminate\Support\Collection;

class Utils
{
    public static function orderColumnName(): string
    {
        return config('filament-tree.column_name.order', 'order');
    }

    public static function parentColumnName(): string
    {
        return config('filament-tree.column_name.parent', 'parent_id');
    }

    public static function titleColumnName(): string
    {
        return config('filament-tree.column_name.title', 'title');
    }

    /**
     * Get the default parent ID for root nodes.
     * Now returns null by default (proper Laravel convention).
     * Falls back to legacy -1 if configured.
     */
    public static function defaultParentId(): ?int
    {
        $default = config('filament-tree.default_parent_id');

        // If explicitly set to null, use null
        if ($default === null) {
            return null;
        }

        return (int) $default;
    }

    /**
     * Legacy support: some databases still use -1 as default parent.
     */
    public static function legacyDefaultParentId(): int
    {
        return (int) config('filament-tree.legacy_default_parent_id', -1);
    }

    /**
     * Check if a value represents a root node.
     */
    public static function isRootKey($value): bool
    {
        $default = static::defaultParentId();

        if ($default === null && $value === null) {
            return true;
        }

        if ($default !== null && (int) $value === $default) {
            return true;
        }

        // Legacy: also check -1 for backwards compatibility
        if ((int) $value === static::legacyDefaultParentId()) {
            return true;
        }

        return false;
    }

    public static function defaultChildrenKeyName(): string
    {
        return strval(config('filament-tree.default_children_key', 'children'));
    }

    /**
     * Build a nested array from flat nodes.
     * Now handles both null and -1 as parent IDs for root nodes.
     *
     * @param  array|Collection  $nodes
     */
    public static function buildNestedArray(
        $nodes = [],
        int|string|null $parentId = null,
        ?string $primaryKeyName = null,
        ?string $parentKeyName = null,
        ?string $childrenKeyName = null): array
    {
        $branch = [];

        $primaryKeyName = $primaryKeyName ?: 'id';
        $parentKeyName = $parentKeyName ?: static::parentColumnName();
        $childrenKeyName = $childrenKeyName ?: static::defaultChildrenKeyName();

        $nodeGroups = collect($nodes)->groupBy(fn ($node) => $node[$parentKeyName] ?? '__ROOT__')->sortKeys();

        foreach ($nodeGroups as $pk => $nodeGroup) {
            // Normalize parent key: null, '', '__ROOT__', or legacy -1/0
            $normalizedPk = ($pk === '__ROOT__' || $pk === '' || $pk === null) ? null : (is_numeric($pk) ? (int) $pk : $pk);
            $normalizedParentId = ($parentId === '__ROOT__' || $parentId === '' || $parentId === null) ? null : (is_numeric($parentId) ? (int) $parentId : $parentId);

            $isMatch = false;

            // Both null (root match)
            if ($normalizedPk === null && $normalizedParentId === null) {
                $isMatch = true;
            }
            // Same numeric value
            elseif ($normalizedPk !== null && $normalizedParentId !== null && $normalizedPk === $normalizedParentId) {
                $isMatch = true;
            }
            // Legacy: -1 or 0 as parent ID matching root
            elseif (static::isRootKey($pk) && static::isRootKey($parentId)) {
                $isMatch = true;
            }

            if ($isMatch) {
                foreach ($nodeGroup as $node) {
                    $node = collect($node)->toArray();
                    $nodePk = $node[$primaryKeyName] ?? null;

                    array_push($branch, array_merge($node, [
                        $childrenKeyName => static::buildNestedArray(
                            nodes: $nodes,
                            parentId: $nodePk,
                            primaryKeyName: $primaryKeyName,
                            parentKeyName: $parentKeyName,
                            childrenKeyName: $childrenKeyName
                        ),
                    ]));
                }
            }
        }

        return $branch;
    }

    /**
     * Filter tree nodes by search term (recursive).
     */
    public static function filterTreeBySearch(array $nodes, string $searchTerm, string $titleKey = 'title', string $childrenKey = 'children'): array
    {
        $searchTerm = mb_strtolower(trim($searchTerm));

        if (empty($searchTerm)) {
            return $nodes;
        }

        $result = [];

        foreach ($nodes as $node) {
            $title = mb_strtolower($node[$titleKey] ?? '');
            $children = $node[$childrenKey] ?? [];

            $match = str_contains($title, $searchTerm);
            $filteredChildren = [];

            if (! empty($children)) {
                $filteredChildren = static::filterTreeBySearch($children, $searchTerm, $titleKey, $childrenKey);
            }

            // Keep node if it matches OR has matching children
            if ($match || ! empty($filteredChildren)) {
                $node[$childrenKey] = $filteredChildren;
                $node['_searchMatch'] = $match;
                $node['_hasSearchMatch'] = $match || ! empty($filteredChildren);
                $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * Highlight search term in text.
     */
    public static function highlightSearchTerm(string $text, string $searchTerm): string
    {
        if (empty($searchTerm)) {
            return e($text);
        }

        $pattern = '/(' . preg_quote($searchTerm, '/') . ')/iu';

        return preg_replace($pattern, '<mark class="filament-tree-search-highlight">$1</mark>', e($text));
    }
}
