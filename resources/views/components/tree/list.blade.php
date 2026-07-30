@props([
    'records',
    'containerKey',
    'tree',
    'collapsed' => null,
])
<ol @class([
    'filament-tree-list',
    'dd-list',
    'hidden' => $collapsed,
])
    role="group"
    aria-label="{{ $attributes->get('aria-label') ?? __('filament-tree::filament-tree.tree_label') }}">
    @foreach ($records ?? [] as $record)
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
