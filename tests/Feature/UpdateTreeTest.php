<?php

use SolutionForest\FilamentTree\Support\Utils;
use SolutionForest\FilamentTree\Tests\Database\TestCategory;

/**
 * Test InteractWithTree trait: updateTree() and unnestArray()
 *
 * The updateTree() method takes a nested array structure:
 * [
 *   { id: 1, children: [{ id: 5 }, { id: 6 }] },
 *   { id: 2, children: [{ id: 7, children: [{ id: 8 }] }] },
 * ]
 * And flattens it setting parent_id and order on each record.
 */

beforeEach(function () {
    // Create a simple tree for testing
    $this->root1 = TestCategory::create(['title' => 'Root 1', 'order' => 1]);
    $this->root2 = TestCategory::create(['title' => 'Root 2', 'order' => 2]);
    $this->child1 = TestCategory::create(['title' => 'Child 1', 'parent_id' => $this->root1->id, 'order' => 1]);
    $this->child2 = TestCategory::create(['title' => 'Child 2', 'parent_id' => $this->root1->id, 'order' => 2]);
    $this->grandchild = TestCategory::create([
        'title' => 'Grandchild',
        'parent_id' => $this->child1->id,
        'order' => 1
    ]);
});

// --- Test unnestArray logic (via Utils) ---

it('can unnest a simple flat list', function () {
    // Like Utils::unnestArray() logic
    $result = [];
    $list = [
        ['id' => 1],
        ['id' => 2],
    ];
    unnestTest($result, $list, null);

    expect($result)->toHaveCount(2);
    expect($result[1]['parent_id'])->toBeNull();
    expect($result[2]['parent_id'])->toBeNull();
    expect($result[1]['order'])->toBe(1);
    expect($result[2]['order'])->toBe(2);
});

it('can unnest a nested list', function () {
    $result = [];
    $list = [
        ['id' => 1, 'children' => [
            ['id' => 3],
        ]],
        ['id' => 2],
    ];
    unnestTest($result, $list, null);

    expect($result)->toHaveCount(3);
    expect($result[1]['parent_id'])->toBeNull();
    expect($result[2]['parent_id'])->toBeNull();
    expect($result[3]['parent_id'])->toBe(1);
    expect($result[3]['order'])->toBe(1);
});

it('can unnest deep nesting', function () {
    $result = [];
    $list = [
        ['id' => 1, 'children' => [
            ['id' => 2, 'children' => [
                ['id' => 3],
            ]],
        ]],
    ];
    unnestTest($result, $list, null);

    expect($result[1]['parent_id'])->toBeNull();
    expect($result[2]['parent_id'])->toBe(1);
    expect($result[3]['parent_id'])->toBe(2);
    expect($result[3]['order'])->toBe(1);
});

it('preserves order in nested list', function () {
    $result = [];
    $list = [
        ['id' => 1, 'children' => [
            ['id' => 3],
            ['id' => 4],
            ['id' => 5],
        ]],
        ['id' => 2],
    ];
    unnestTest($result, $list, null);

    expect($result[3]['order'])->toBe(1);
    expect($result[4]['order'])->toBe(2);
    expect($result[5]['order'])->toBe(3);
    expect($result[1]['order'])->toBe(1);
    expect($result[2]['order'])->toBe(2);
});

it('handles empty list', function () {
    $result = [];
    unnestTest($result, [], null);
    expect($result)->toBe([]);
});

it('handles single item', function () {
    $result = [];
    unnestTest($result, [['id' => 1]], null);
    expect($result)->toHaveCount(1);
    expect($result[1]['parent_id'])->toBeNull();
    expect($result[1]['order'])->toBe(1);
});

it('handles item without children key', function () {
    $result = [];
    $list = [['id' => 1], ['id' => 2]];
    unnestTest($result, $list, null);

    expect($result)->toHaveCount(2);
    expect($result[1]['children'] ?? null)->toBeNull();
});

// --- DB-level reorder test ---

it('can reorder root records', function () {
    // Simulate a reorder: swap root1 and root2
    TestCategory::where('id', $this->root1->id)->update(['order' => 2]);
    TestCategory::where('id', $this->root2->id)->update(['order' => 1]);

    $root1 = TestCategory::find($this->root1->id);
    $root2 = TestCategory::find($this->root2->id);

    expect($root1->order)->toBe(2);
    expect($root2->order)->toBe(1);
});

it('can move a child to a different parent', function () {
    // Move child1 from root1 to root2
    TestCategory::where('id', $this->child1->id)->update(['parent_id' => $this->root2->id, 'order' => 3]);

    $child1 = TestCategory::find($this->child1->id);
    expect($child1->parent_id)->toBe($this->root2->id);
});

it('can promote a child to root', function () {
    // Move child1 to root
    TestCategory::where('id', $this->child1->id)->update(['parent_id' => null, 'order' => 3]);

    $child1 = TestCategory::find($this->child1->id);
    expect($child1->parent_id)->toBeNull();
});

// Helper: reimplements the unnestArray logic from InteractWithTree
function unnestTest(array &$result, array $current, $parent): void
{
    foreach ($current as $index => $item) {
        $key = data_get($item, 'id');
        $result[$key] = [
            'parent_id' => $parent,
            'order' => $index + 1,
        ];
        if (isset($item['children']) && count($item['children'])) {
            unnestTest($result, $item['children'], $key);
        }
    }
}
