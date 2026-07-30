/**
 * Filament Tree - Main Entry Point
 *
 * This file is loaded on every Filament page. It registers the Alpine component
 * with a lazy-loading proxy. The full implementation (including SortableJS) is
 * loaded via dynamic import() only when init() is called (i.e., when a tree
 * component is actually present on the page).
 *
 * This keeps SortableJS (42 KB) off pages that don't have a tree, while
 * ensuring proper Alpine component registration at alpine:init time.
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('treeNestableComponent', (config) => ({
        sortableInstances: [],
        isLoading: false,
        initialized: false,
        expandedNodes: new Set(),
        autoExpandTimer: null,
        autoExpandTarget: null,

        async init() {
            try {
                // Dynamically import the real implementation (includes SortableJS)
                const mod = await import('./components/filament-tree-component.js');
                const realFactory = mod.default || mod;
                const realComponent = realFactory(config);

                // Copy all methods from the real component onto this instance
                for (const key of Object.keys(realComponent)) {
                    if (typeof realComponent[key] === 'function') {
                        this[key] = realComponent[key].bind(this);
                    } else {
                        this[key] = realComponent[key];
                    }
                }

                // Call the real init (this sets up SortableJS, events, keyboard)
                if (typeof this.init === 'function') {
                    // But we're already in init(), so call setup directly
                    this.treeElement = document.getElementById(this.containerKey);
                    if (!this.treeElement) {
                        console.warn('[Filament Tree] Container not found');
                        return;
                    }
                    this.initSortable();
                    this.initEventDelegation();
                    this.initKeyboardNavigation();
                    this.initialized = true;
                }
            } catch (err) {
                console.error('[Filament Tree] Failed to load component:', err);
            }
        },
    }));
});
