@php
    $containerKey = 'filament_tree_container_' . $this->getId();
    $maxDepth = $getMaxDepth() ?? 1;
    $records = collect($this->getRootLayerRecords() ?? []);
    $toolbarActions = $tree->getToolbarActions() ?? [];
    $searchEnabled = config('filament-tree.search.enabled', true);
    $searchPlaceholder = config('filament-tree.search.placeholder', 'Search tree...');
    $lazyLoading = config('filament-tree.lazy_loading.enabled', false);
@endphp

<div class="filament-tree-component"
    wire:disabled="updateTree"
    x-data="treeNestableComponent({
        containerKey: '{{ $containerKey }}',
        maxDepth: {{ $maxDepth }},
        searchEnabled: {{ $searchEnabled ? 'true' : 'false' }},
        lazyLoading: {{ $lazyLoading ? 'true' : 'false' }}
    })">
    <x-filament::section>
        <x-slot name="heading">
            {{ ($this->displayTreeTitle() ?? false) ? $this->getTreeTitle() : null }}
        </x-slot>

        {{-- SEARCH BAR --}}
        @if($searchEnabled)
        <div class="filament-tree-search-wrapper" x-show="searchEnabledActual">
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    type="text"
                    x-model="searchTerm"
                    x-on:input.debounce.300ms="search()"
                    placeholder="{{ $searchPlaceholder }}"
                    class="filament-tree-search-input pl-10"
                    aria-label="{{ $searchPlaceholder }}"
                />
                <button
                    x-show="searchTerm.length > 0"
                    x-on:click="searchTerm = ''; search()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    type="button"
                    aria-label="Clear search"
                >
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        @endif

        {{-- TOOLBAR --}}
        <menu class="nestable-menu" id="nestable-menu">
            <div class="toolbar-btns main">
                <div class="btn-group">
                    <x-filament::button color="gray" tag="button" data-action="expand-all" x-on:click="expandAll()" wire:loading.attr="disabled" wire:loading.class="cursor-wait opacity-70">
                        {{ __('filament-tree::filament-tree.button.expand_all') }}
                    </x-filament::button>
                    <x-filament::button color="gray" tag="button" data-action="collapse-all" x-on:click="collapseAll()" wire:loading.attr="disabled" wire:loading.class="cursor-wait opacity-70">
                        {{ __('filament-tree::filament-tree.button.collapse_all') }}
                    </x-filament::button>
                </div>
                <div class="btn-group">
                    <x-filament::button tag="button" data-action="save" x-on:click="save()" wire:loading.attr="disabled" wire:loading.class="cursor-wait opacity-70">
                        <x-filament::loading-indicator class="h-4 w-4" wire:loading wire:target="updateTree"/>
                        <span wire:loading.remove wire:target="updateTree">
                            {{ __('filament-tree::filament-tree.button.save') }}
                        </span>
                    </x-filament::button>
                </div>
            </div>

            @if (is_array($toolbarActions) && count($toolbarActions))
                <x-filament::actions 
                    class="toolbar-btns"
                    :actions="$toolbarActions"
                />
            @endif
        </menu>

        {{-- LOADING OVERLAY --}}
        <div x-show="isLoading" class="filament-tree-loading-overlay">
            <div class="filament-tree-loading-spinner"></div>
        </div>

        {{-- TREE --}}
        {{-- x-ignore prevents Alpine from re-rendering the tree DOM
             which would conflict with SortableJS DOM manipulation --}}
        <div class="filament-tree dd" id="{{ $containerKey }}" x-ignore>
            <ol class="filament-tree-list dd-list" role="tree" aria-label="{{ __('filament-tree::filament-tree.tree_label') }}">
            @foreach($records as $record)
                @php
                    $title = $this->getTreeRecordTitle($record);
                    $icon = $this->getTreeRecordIcon($record);
                @endphp
                <x-filament-tree::tree.item
                    :record="$record"
                    :containerKey="$containerKey"
                    :tree="$tree"
                    :title="$title"
                    :description="$this->getTreeRecordDescription($record)"
                    :icon="$icon"
                />
            @endforeach
            </ol>
        </div>

        {{-- EMPTY STATE --}}
        @if(count($records) === 0)
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <svg class="mb-4 h-12 w-12 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('filament-tree::filament-tree.empty') }}</p>
        </div>
        @endif
    </x-filament::section>
</div>