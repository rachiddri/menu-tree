<?php

namespace SolutionForest\FilamentTree\Concern;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use SolutionForest\FilamentTree\Support\Utils;

trait HasRecords
{
    protected ?Collection $records = null;

    #[On('refreshTree')]
    public function getRecords(): ?Collection
    {
        if ($this->records) {
            return $this->records;
        }

        return $this->records = $this->getSortedQuery()->get();
    }

    /**
     * Get the base query for records, can be overridden for custom filtering.
     */
    public function getRecordsQuery(): Builder
    {
        return $this->getTreeQuery();
    }

    public function getTreeRecord(?string $key): ?Model
    {
        return $this->resolveTreeRecord($key);
    }

    protected function resolveTreeRecord(?string $key): ?Model
    {
        if ($key === null) {
            return null;
        }

        return $this->getSortedQuery()->find($key);
    }

    /**
     * Resolve the model instance from the model class name.
     */
    protected function resolveModelInstance(): ?Model
    {
        $modelClass = $this->getModel();

        if (is_string($modelClass) && class_exists($modelClass)) {
            return app($modelClass);
        }

        if ($modelClass instanceof Model) {
            return $modelClass;
        }

        return null;
    }

    /**
     * Get root layer records (first level of the tree).
     * Supports both null parent_id (new) and -1 (legacy).
     */
    public function getRootLayerRecords(): \Illuminate\Support\Collection
    {
        $query = $this->getRecordsQuery();
        $modelInstance = $this->resolveModelInstance();

        if (! $modelInstance) {
            return collect();
        }

        $parentColumn = method_exists($modelInstance, 'determineParentColumnName')
            ? $modelInstance->determineParentColumnName()
            : 'parent_id';

        $defaultParent = method_exists($modelInstance, 'defaultParentKey')
            ? $modelInstance::defaultParentKey()
            : Utils::defaultParentId();

        $orderColumn = method_exists($modelInstance, 'determineOrderColumnName')
            ? $modelInstance->determineOrderColumnName()
            : 'order';

        // Handle null parent_id (new default)
        if ($defaultParent === null) {
            return $query->whereNull($parentColumn)
                ->orderBy($orderColumn)
                ->get();
        }

        // Legacy -1 or other numeric default
        return $this->getRecords()
            ->filter(function (Model $record) use ($parentColumn, $defaultParent) {
                $value = $record->getAttributeValue($parentColumn);

                // null also counts as root
                if ($value === null) {
                    return true;
                }

                return (int) $value === $defaultParent || (int) $value === -1;
            })
            ->sortBy(function ($record) use ($orderColumn) {
                return $record->{$orderColumn} ?? 0;
            })
            ->values();
    }

    /**
     * Check if a model class has a method (static or instance).
     */
    protected function modelHasMethod(string $methodName): bool
    {
        $modelClass = $this->getModel();

        if (is_string($modelClass)) {
            return method_exists($modelClass, $methodName);
        }

        if ($modelClass instanceof Model) {
            return method_exists($modelClass, $methodName);
        }

        return false;
    }

    protected function getSortedQuery(): Builder
    {
        $query = $this->getWithRelationQuery();

        if ($this->modelHasMethod('scopeOrdered')) {
            return $this->getWithRelationQuery()->ordered();
        }

        return $query;
    }

    protected function getWithRelationQuery(): Builder
    {
        $query = $this->getTreeQuery();
        $modelClass = $this->getModel();

        if (is_string($modelClass) && method_exists($modelClass, 'children') && $modelClass::has('children')) {
            return $query->with('children');
        }

        return $query;
    }

    protected function getTreeQuery(): Builder
    {
        $modelClass = $this->getModel();

        if (is_string($modelClass)) {
            return $modelClass::query();
        }

        return $modelClass->query();
    }
}
