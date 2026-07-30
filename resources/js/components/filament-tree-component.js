/**
 * Filament Tree Component v3.0
 *
 * Drag-and-drop tree using SortableJS with nesting support.
 * Replaces jQuery Nestable with modern vanilla JS.
 *
 * Features:
 * - SortableJS drag & drop with proper nestable behavior
 * - Search/filter with live highlighting
 * - Expand/collapse all
 * - Lazy loading for large trees
 * - Dark mode compatible
 */

import Sortable from 'sortablejs';

/**
 * Register the component with Alpine immediately when loaded.
 * This works for both:
 *  - ax-load (script executes in global scope)
 *  - ES module import (entry file re-exports the function)
 */
function treeNestableComponent({
    containerKey,
    maxDepth,
    searchEnabled = true,
    lazyLoading = false,
}) {
    return {
        containerKey,
        maxDepth: maxDepth || 0,
        searchEnabled,
        lazyLoading,

        // Search state
        searchTerm: '',
        searchPlaceholder: 'Search tree...',

        // Tree state
        sortableInstances: [],
        isLoading: false,
        initialized: false,
        expandedNodes: new Set(),

        // Auto-expand during drag
        autoExpandTimer: null,
        autoExpandTarget: null,

        init: function () {
            // Try $refs first (if not using x-ignore), fallback to ID selector
            this.treeElement = this.$refs?.treeContainer || document.getElementById(this.containerKey);

            if (!this.treeElement) {
                console.warn('[Filament Tree] Container not found');
                return;
            }

            this.initSortable();
            this.initEventDelegation();
            this.initKeyboardNavigation();
            this.initialized = true;
        },

        /**
         * Initialize SortableJS on all list levels.
         * Each nested <ol class="dd-list"> becomes a separate sortable group
         * so items can be dragged between levels.
         */
        initSortable: function () {
            // Destroy existing instances first
            this.destroySortable();

            const self = this;
            const lists = this.treeElement.querySelectorAll('.dd-list');

            lists.forEach((list) => {
                const sortable = new Sortable(list, {
                    group: {
                        name: this.containerKey || 'filament-tree',
                        pull: true,
                        put: function (to) {
                            // Allow dropping into any list in the same group
                            return to.el.closest('.filament-tree') !== null;
                        },
                    },
                    animation: 150,
                    easing: 'cubic-bezier(0.25, 0.1, 0.25, 1.0)',
                    handle: '.dd-handle',
                    draggable: '.dd-item',
                    ghostClass: 'dd-ghost',
                    chosenClass: 'dd-chosen',
                    dragClass: 'dd-drag',
                    fallbackOnBody: true,
                    swapThreshold: 0.65,
                    direction: 'vertical',

                    // Prevent dragging from no-drag areas (content, action buttons)
                    filter: '.dd-nodrag, .fi-tree-actions-ctn, input, textarea, select',
                    preventOnFilter: false,
                    // Cursor style during drag
                    delay: 200, // Small delay to distinguish click from drag on touch
                    delayOnTouchOnly: true,

                    // Validate depth on move + auto-expand on hover
                    onMove: function (evt) {
                        if (self.maxDepth > 0 && !self.validateDepth(evt)) {
                            return false;
                        }
                        // Auto-expand collapsed parents when hovering
                        self.handleAutoExpand(evt);
                        return true;
                    },

                    // Save on drop
                    onEnd: function () {
                        self.clearAutoExpand();
                        self.save();
                    },

                    onStart: function () {
                        // Nothing yet
                    },

                    // Clean up auto-expand if drag is cancelled
                    onCancel: function () {
                        self.clearAutoExpand();
                    },
                });

                self.sortableInstances.push(sortable);
            });
        },

        /**
         * Remove all SortableJS instances (for re-initialization).
         */
        destroySortable: function () {
            this.sortableInstances.forEach((instance) => {
                if (instance && typeof instance.destroy === 'function') {
                    instance.destroy();
                }
            });
            this.sortableInstances = [];
        },

        /**
         * Validate depth when dragging.
         * Checks if the item can be placed at the target depth.
         */
        validateDepth: function (evt) {
            const dragged = evt.dragged;
            const target = evt.related;

            if (!target) return true;

            // Calculate depth of target position
            let depth = 0;
            let parent = target.closest('.dd-item');
            while (parent) {
                depth++;
                parent = parent.parentElement?.closest('.dd-item');
            }

            // If dropping between items (same level)
            if (evt.willInsertAfter || evt.willInsertBefore) {
                // Same depth - check it's within max
            } else {
                // Dropping onto an item (as child)
                depth++;
            }

            return depth <= this.maxDepth;
        },

        /**
         * Use event delegation for expand/collapse and lazy loading.
         * This handles dynamically added elements (lazy loaded children).
         */
        initEventDelegation: function () {
            const self = this;

            this.treeElement?.addEventListener('click', function (e) {
                const expandBtn = e.target.closest('[data-action=expand]');
                const collapseBtn = e.target.closest('[data-action=collapse]');

                if (expandBtn) {
                    e.stopPropagation();
                    self.expandNode(expandBtn);
                } else if (collapseBtn) {
                    e.stopPropagation();
                    self.collapseNode(collapseBtn);
                }
            });

            // If lazy loading is enabled, intercept expand to load children first
            if (this.lazyLoading) {
                this.treeElement?.addEventListener('click', function (e) {
                    const expandBtn = e.target.closest('[data-action=expand]');
                    if (expandBtn) {
                        const item = expandBtn.closest('.dd-item');
                        if (item && !item.querySelector(':scope > .dd-list') && !item.dataset.loaded) {
                            e.preventDefault();
                            e.stopPropagation();
                            self.loadChildren(item, expandBtn);
                        }
                    }
                });
            }
        },

        /**
         * Load children via Livewire (lazy loading).
         */
        loadChildren: async function (item, expandBtn) {
            const parentId = item.dataset.id;
            if (!parentId) return;

            this.isLoading = true;
            item.classList.add('dd-loading');

            try {
                const result = await this.$wire.loadTreeChildren(parseInt(parentId));
                if (result && result.html) {
                    // Create a temporary container and extract the list
                    const temp = document.createElement('div');
                    temp.innerHTML = result.html;
                    const list = temp.querySelector('.dd-list');

                    if (list) {
                        item.appendChild(list);

                        // Mark as loaded and initialize Sortable on the new list
                        item.dataset.loaded = 'true';
                        this.initSortable();
                    }
                }
            } catch (error) {
                console.error('[Filament Tree] Failed to load children', error);
            } finally {
                this.isLoading = false;
                item.classList.remove('dd-loading');
            }

            // Now expand the node
            this.expandNode(expandBtn);
        },

        /**
         * Expand a tree node.
         */
        expandNode: function (button) {
            const item = button.closest('.dd-item');
            if (!item) return;

            // Toggle button visibility
            button.classList.add('hidden');
            const collapseBtn = item.querySelector('[data-action=collapse]');
            if (collapseBtn) collapseBtn.classList.remove('hidden');

            // Show the child list
            const childList = item.querySelector(':scope > .dd-list');
            if (childList) {
                childList.classList.remove('hidden');
                childList.style.display = '';

                // Show all direct children
                childList.querySelectorAll(':scope > .dd-item').forEach((child) => {
                    child.classList.remove('dd-collapsed');
                });
            }

            this.expandedNodes.add(item.dataset.id);
        },

        /**
         * Collapse a tree node.
         */
        collapseNode: function (button) {
            const item = button.closest('.dd-item');
            if (!item) return;

            // Toggle button visibility
            button.classList.add('hidden');
            const expandBtn = item.querySelector('[data-action=expand]');
            if (expandBtn) expandBtn.classList.remove('hidden');

            // Hide the child list
            const childList = item.querySelector(':scope > .dd-list');
            if (childList) {
                childList.classList.add('hidden');
                childList.style.display = 'none';

                // Hide all children
                childList.querySelectorAll(':scope > .dd-item').forEach((child) => {
                    child.classList.add('dd-collapsed');
                });
            }

            this.expandedNodes.delete(item.dataset.id);
        },

        /**
         * Save the tree state via Livewire.
         */
        save: async function () {
            const serialized = this.serialize();
            this.isLoading = true;

            try {
                const result = await this.$wire.updateTree(serialized);
                if (result?.reload === true) {
                    // Trigger refresh
                    this.$wire.$refresh();
                }
            } catch (error) {
                console.error('[Filament Tree] Failed to save', error);
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Serialize the tree to a nested array.
         */
        serialize: function () {
            const rootList = this.treeElement?.querySelector(':scope > .dd-list');
            if (!rootList) return [];

            return this.serializeList(rootList);
        },

        /**
         * Serialize a single list level.
         */
        serializeList: function (list) {
            const items = [];
            const children = list.querySelectorAll(':scope > .dd-item');

            children.forEach((item) => {
                const id = item.dataset.id;
                const childList = item.querySelector(':scope > .dd-list');

                const entry = { id: parseInt(id) };
                if (childList && childList.querySelector(':scope > .dd-item')) {
                    entry.children = this.serializeList(childList);
                }

                items.push(entry);
            });

            return items;
        },

        /**
         * Expand all nodes.
         */
        expandAll: function () {
            const self = this;
            const items = this.treeElement?.querySelectorAll('.dd-item');

            items?.forEach((item) => {
                const expandBtn = item.querySelector('[data-action=expand]');
                if (expandBtn && !expandBtn.classList.contains('hidden')) {
                    self.expandNode(expandBtn);
                }
            });
        },

        /**
         * Collapse all nodes.
         */
        collapseAll: function () {
            const self = this;
            const items = this.treeElement?.querySelectorAll('.dd-item');

            items?.forEach((item) => {
                const collapseBtn = item.querySelector('[data-action=collapse]');
                if (collapseBtn && !collapseBtn.classList.contains('hidden')) {
                    self.collapseNode(collapseBtn);
                }
            });
        },

        /**
         * Search/filter the tree with live highlighting.
         */
        search: function () {
            const term = this.searchTerm?.toLowerCase().trim() || '';

            if (term.length > 0 && term.length < 2) {
                return; // Wait for at least 2 characters
            }

            const items = this.treeElement?.querySelectorAll('.dd-item');

            items?.forEach((item) => {
                const titleEl = item.querySelector('.dd-title');
                if (!titleEl) return;

                // Store original text on first search
                if (!titleEl.dataset.originalText) {
                    titleEl.dataset.originalText = titleEl.textContent || '';
                }

                const title = titleEl.dataset.originalText.toLowerCase();
                const match = term.length === 0 || title.includes(term);

                if (match && term.length > 0) {
                    // Highlight matching text
                    const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                    titleEl.innerHTML = titleEl.dataset.originalText.replace(
                        regex,
                        '<mark class="filament-tree-search-highlight">$1</mark>'
                    );
                } else {
                    // Restore original text
                    titleEl.innerHTML = titleEl.dataset.originalText;
                }

                // Show/hide based on match
                item.style.display = match ? '' : 'none';

                // If this item matches, ensure all parent items are visible
                if (match) {
                    let parent = item.parentElement?.closest('.dd-item');
                    while (parent) {
                        parent.style.display = '';
                        parent = parent.parentElement?.closest('.dd-item');
                    }
                }
            });
        },

        /**
         * Initialize keyboard navigation (arrow keys, enter, space).
         */
        initKeyboardNavigation: function () {
            const self = this;

            this.treeElement?.addEventListener('keydown', function (e) {
                const item = e.target.closest('[role="treeitem"]');
                if (!item) return;

                switch (e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        self.focusNextItem(item);
                        break;

                    case 'ArrowUp':
                        e.preventDefault();
                        self.focusPreviousItem(item);
                        break;

                    case 'ArrowRight':
                        e.preventDefault();
                        self.expandOrFocusFirstChild(item);
                        break;

                    case 'ArrowLeft':
                        e.preventDefault();
                        self.collapseOrFocusParent(item);
                        break;

                    case 'Home':
                        e.preventDefault();
                        self.focusFirstItem();
                        break;

                    case 'End':
                        e.preventDefault();
                        self.focusLastItem();
                        break;

                    case 'Enter':
                    case ' ': // Space
                        e.preventDefault();
                        self.activateItem(item);
                        break;
                }
            });

            // Make items focusable and handle focus
            this.treeElement?.addEventListener('focusin', function (e) {
                const item = e.target.closest('[role="treeitem"]');
                if (item) {
                    item.setAttribute('tabindex', '0');
                }
            });
        },

        /**
         * Focus the next visible tree item.
         */
        focusNextItem: function (current) {
            const allItems = this.getVisibleItems();
            const index = allItems.indexOf(current);
            if (index < allItems.length - 1) {
                allItems[index + 1]?.focus();
            }
        },

        /**
         * Focus the previous visible tree item.
         */
        focusPreviousItem: function (current) {
            const allItems = this.getVisibleItems();
            const index = allItems.indexOf(current);
            if (index > 0) {
                allItems[index - 1]?.focus();
            }
        },

        /**
         * Get all currently visible (not hidden) tree items.
         */
        getVisibleItems: function () {
            const root = this.treeElement?.querySelector('[role="tree"]');
            if (!root) return [];

            const allItems = root.querySelectorAll('[role="treeitem"]:not(.hidden):not(.dd-collapsed)');
            return Array.from(allItems).filter(item => {
                // Check if item or any parent is hidden
                let parent = item.parentElement;
                while (parent && parent !== root) {
                    if (parent.classList.contains('hidden')) return false;
                    parent = parent.parentElement;
                }
                return true;
            });
        },

        /**
         * Focus the first item in the tree.
         */
        focusFirstItem: function () {
            const items = this.getVisibleItems();
            if (items.length > 0) items[0]?.focus();
        },

        /**
         * Focus the last item in the tree.
         */
        focusLastItem: function () {
            const items = this.getVisibleItems();
            if (items.length > 0) items[items.length - 1]?.focus();
        },

        /**
         * Expand a collapsed node or focus its first child.
         */
        expandOrFocusFirstChild: function (item) {
            const expandBtn = item.querySelector('[data-action=expand]:not(.hidden)');
            if (expandBtn) {
                this.expandNode(expandBtn);
                return;
            }
            // Focus first child if expanded
            const childList = item.querySelector(':scope > .dd-list:not(.hidden)');
            if (childList) {
                const firstChild = childList.querySelector('[role="treeitem"]');
                if (firstChild) firstChild.focus();
            }
        },

        /**
         * Collapse an expanded node or focus its parent.
         */
        collapseOrFocusParent: function (item) {
            const collapseBtn = item.querySelector('[data-action=collapse]:not(.hidden)');
            if (collapseBtn) {
                this.collapseNode(collapseBtn);
                return;
            }
            // Focus parent
            const parentItem = item.parentElement?.closest('[role="treeitem"]');
            if (parentItem) parentItem.focus();
        },

        /**
         * Activate a tree item (toggle expand/collapse or open action).
         */
        activateItem: function (item) {
            // If item has children, toggle expand/collapse
            const expandBtn = item.querySelector('[data-action=expand]:not(.hidden)');
            const collapseBtn = item.querySelector('[data-action=collapse]:not(.hidden)');

            if (expandBtn) {
                this.expandNode(expandBtn);
            } else if (collapseBtn) {
                this.collapseNode(collapseBtn);
            }
            // Otherwise, the item's default action (e.g., edit) could be triggered
        },

        /**
         * Auto-expand collapsed parent when hovering during drag.
         * Uses SortableJS onMove event data.
         */
        handleAutoExpand: function (evt) {
            const target = evt.related;
            if (!target) {
                this.clearAutoExpand();
                return;
            }

            // Find the closest tree item (the potential parent)
            const parentItem = target.closest('[role="treeitem"]');
            if (!parentItem) {
                this.clearAutoExpand();
                return;
            }

            // Check if the item has children and is collapsed
            const expandBtn = parentItem.querySelector('[data-action=expand]:not(.hidden)');
            if (!expandBtn) {
                // Item is already expanded or has no children
                this.clearAutoExpand();
                return;
            }

            // If hovering over a new target, reset the timer
            if (this.autoExpandTarget !== parentItem) {
                this.clearAutoExpand();
                this.autoExpandTarget = parentItem;
                this.autoExpandTimer = setTimeout(() => {
                    if (this.autoExpandTarget) {
                        this.expandNode(expandBtn);
                    }
                    this.autoExpandTimer = null;
                    this.autoExpandTarget = null;
                }, 600); // Expand after 600ms of hover
            }
        },

        /**
         * Clear the auto-expand timer.
         */
        clearAutoExpand: function () {
            if (this.autoExpandTimer) {
                clearTimeout(this.autoExpandTimer);
                this.autoExpandTimer = null;
            }
            this.autoExpandTarget = null;
        },
    };
}

// Auto-register with Alpine when loaded (works with ax-load and directly)
try {
    if (typeof Alpine !== 'undefined' && Alpine) {
        Alpine.data('treeNestableComponent', treeNestableComponent);
    }
} catch (e) {
    console.warn('[Filament Tree] Could not register with Alpine:', e);
}

export default treeNestableComponent;
