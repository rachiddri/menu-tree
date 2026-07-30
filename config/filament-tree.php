<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Column Names for Tree Structure
    |--------------------------------------------------------------------------
    |
    | Customize the column names used by the tree structure in your database.
    | You can override these per-model using the ModelTree trait methods.
    |
    */
    'column_name' => [
        'order' => 'order',
        'parent' => 'parent_id',
        'title' => 'title',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Parent ID for Root Nodes
    |--------------------------------------------------------------------------
    |
    | Use null for proper Laravel foreign key conventions.
    | Root nodes have parent_id = null.
    | The old default of -1 is deprecated but still supported for backwards
    | compatibility via the 'legacy_default_parent_id' option.
    |
    */
    'default_parent_id' => null,

    /*
    |--------------------------------------------------------------------------
    | Legacy Default Parent ID
    |--------------------------------------------------------------------------
    |
    | If you're migrating from an older version that used -1 as the default
    | parent ID, set this to -1. The tree will handle both conventions.
    |
    */
    'legacy_default_parent_id' => -1,

    /*
    |--------------------------------------------------------------------------
    | Default Children Relationship Key
    |--------------------------------------------------------------------------
    |
    | The key name used for the children relationship in the tree array.
    |
    */
    'default_children_key' => 'children',

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the search functionality for filtering tree nodes.
    |
    */
    'search' => [
        'enabled' => true,
        'placeholder' => 'Search tree...',
        'debounce' => 300, // milliseconds
        'min_chars' => 2,  // minimum characters before searching
        'highlight_results' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Lazy Loading Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, child nodes are loaded on-demand when expanding a parent
    | node, instead of loading the entire tree at once. Recommended for
    | trees with more than 500 nodes.
    |
    */
    'lazy_loading' => [
        'enabled' => false,
        'load_on_expand' => true,
        'page_size' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Depth
    |--------------------------------------------------------------------------
    |
    | Default maximum nesting depth for the tree. Can be overridden per
    | widget/page using $maxDepth property.
    |
    */
    'max_depth' => 0, // 0 = unlimited
];
