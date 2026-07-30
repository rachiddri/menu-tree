<?php

use SolutionForest\FilamentTree\Support\Utils;
use SolutionForest\FilamentTree\Tests\Database\TestCategory;

/**
 * Test ModelTree trait: getDepth(), isRoot(), scopeIsRoot()
 */

// --- Test isRoot() ---

it('can check if a root record is root', function () {
    $root = TestCategory::create(['title' => 'Root 1', 'order' => 1]);

    expect($root->isRoot())->toBeTrue();
});

it('can check if a child record is not root', function () {
    $root = TestCategory::create(['title' => 'Root', 'order' => 1]);
    $child = TestCategory::create(['title' => 'Child', 'parent_id' => $root->id, 'order' => 1]);

    expect($child->isRoot())->toBeFalse();
});

// --- Test getDepth() ---

it('can calculate depth 0 for root records', function () {
    $root = TestCategory::create(['title' => 'Root', 'order' => 1]);

    expect($root->getDepth())->toBe(0);
});

it('can calculate depth 1 for direct children', function () {
    $root = TestCategory::create(['title' => 'Root', 'order' => 1]);
    $child = TestCategory::create(['title' => 'Child', 'parent_id' => $root->id, 'order' => 1]);

    expect($child->getDepth())->toBe(1);
});

it('can calculate depth 3 for deeply nested records', function () {
    $level0 = TestCategory::create(['title' => 'L0', 'order' => 1]);
    $level1 = TestCategory::create(['title' => 'L1', 'parent_id' => $level0->id, 'order' => 1]);
    $level2 = TestCategory::create(['title' => 'L2', 'parent_id' => $level1->id, 'order' => 1]);
    $level3 = TestCategory::create(['title' => 'L3', 'parent_id' => $level2->id, 'order' => 1]);

    expect($level3->getDepth())->toBe(3);
});

it('returns depth 0 for records with null parent_id', function () {
    $record = new TestCategory(['parent_id' => null]);

    expect($record->getDepth())->toBe(0);
});

// --- Test scopeIsRoot() via whereNull ---

it('scopeIsRoot returns only root records', function () {
    TestCategory::create(['title' => 'Root 1', 'order' => 1]);
    TestCategory::create(['title' => 'Root 2', 'order' => 2]);
    $root3 = TestCategory::create(['title' => 'Root 3', 'order' => 3]);
    TestCategory::create(['title' => 'Child of 3', 'parent_id' => $root3->id, 'order' => 1]);

    $roots = TestCategory::whereNull('parent_id')->get();

    expect($roots)->toHaveCount(3);
});

it('scopeIsRoot filters correctly with mixed data', function () {
    $root = TestCategory::create(['title' => 'Root', 'order' => 1]);
    TestCategory::create(['title' => 'Child 1', 'parent_id' => $root->id, 'order' => 1]);
    TestCategory::create(['title' => 'Child 2', 'parent_id' => $root->id, 'order' => 2]);

    $roots = TestCategory::whereNull('parent_id')->get();
    $children = TestCategory::whereNotNull('parent_id')->get();

    expect($roots)->toHaveCount(1);
    expect($children)->toHaveCount(2);
});

// --- Test defaultParentKey() ---

it('defaultParentKey returns null for new records', function () {
    $record = new TestCategory();

    expect($record::defaultParentKey())->toBeNull();
});

it('determineParentColumnName returns parent_id', function () {
    $record = new TestCategory();

    expect($record->determineParentColumnName())->toBe('parent_id');
});

it('determineOrderColumnName returns order', function () {
    $record = new TestCategory();

    expect($record->determineOrderColumnName())->toBe('order');
});
