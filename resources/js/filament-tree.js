/**
 * Filament Tree - Main Entry Point
 *
 * This file is loaded on every Filament page. It registers the Alpine component
 * with a lightweight stub. The full implementation (including SortableJS) is
 * loaded on-demand via ax-load from filament-tree-component.js.
 *
 * This way, SortableJS (42 KB) is NOT loaded on every page, only when a
 * tree component is actually present.
 */

// Register a placeholder. The real implementation is loaded via ax-load
// from the component file which also calls Alpine.data() on initialization.
document.addEventListener('alpine:init', () => {
    Alpine.data('treeNestableComponent', () => ({}));
});
