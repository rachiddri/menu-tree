<?php

namespace SolutionForest\FilamentTree\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentTree\Concern\ModelTree;

class TestCategory extends Model
{
    use ModelTree;

    protected $table = 'test_categories';

    protected $fillable = [
        'parent_id',
        'title',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }
}
