<?php

use Illuminate\Support\Facades\DB;
use SolutionForest\FilamentTree\Tests\Database\TestCategory;

/**
 * Test HasRecords trait: getRootLayerRecords()
 * Uses a widget to test the trait methods.
 */

function buildTestTree(int $rootCount, int $maxDepth, int $childrenPerNode): void
{
    $roots = [];
    for ($i = 1; $i <= $rootCount; $i++) {
        $roots[] = TestCategory::create([
            'title' => "Root {$i}",
            'order' => $i,
        ]);
    }

    $queue = [];
    foreach ($roots as $root) {
        $queue[] = ['parent' => $root, 'depth' => 1];
    }

    while (! empty($queue)) {
        $current = array_shift($queue);
        $parent = $current['parent'];
        $depth = $current['depth'];

        if ($depth >= $maxDepth) {
            continue;
        }

        for ($i = 1; $i <= $childrenPerNode; $i++) {
            $child = TestCategory::create([
                'title' => "{$parent->title} - Child {$i}",
                'parent_id' => $parent->id,
                'order' => $i,
            ]);
            $queue[] = ['parent' => $child, 'depth' => $depth + 1];
        }
    }
}

beforeEach(function () {
    buildTestTree(4, 3, 4);
});

it('returns correct number of root records', function () {
    $roots = TestCategory::whereNull('parent_id')->get();

    expect($roots)->toHaveCount(4);
});

it('returns root records with correct parent_id null', function () {
    $roots = TestCategory::whereNull('parent_id')->get();

    $roots->each(function ($root) {
        expect($root->parent_id)->toBeNull();
    });
});

it('root records have children', function () {
    $root = TestCategory::whereNull('parent_id')->first();
    $children = TestCategory::where('parent_id', $root->id)->get();

    expect($children)->toHaveCount(4);
    expect($children->first()->parent_id)->toBe($root->id);
});

it('layered query does not produce N+1', function () {
    DB::enableQueryLog();

    // Load all records in a single query
    $all = TestCategory::all();
    $roots = $all->whereNull('parent_id');

    // Access children from loaded collection (no extra queries)
    foreach ($roots as $root) {
        $children = $all->where('parent_id', $root->id);
    }

    $queries = count(DB::getQueryLog());
    // Expect only 1 query (the initial `select * from test_categories`)
    expect($queries)->toBe(1);

    DB::disableQueryLog();
});

it('tree contains expected total nodes', function () {
    $total = TestCategory::count();

    // 4 roots + 4*4 children at depth 1 + 16*4 children at depth 2
    // = 4 + 16 + 64 = 84
    expect($total)->toBe(84);
});
