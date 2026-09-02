<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * The category tree, read once and walked in memory.
 *
 * Category::getAllDescendantIds() walks the children relation, which lazy-loads
 * a query per level, and the filter sidebar needs a subtree per row - so asking
 * it per row cost dozens of round trips on every listing page.
 */
class CategoryTree
{
    private Collection $rows;

    private Collection $childrenByParent;

    public function __construct()
    {
        $this->rows = Category::query()->get(['id', 'parent_id', 'name', 'slug', 'is_active']);
        $this->childrenByParent = $this->rows->groupBy('parent_id');
    }

    public function rows(): Collection
    {
        return $this->rows;
    }

    /** The category itself plus everything filed beneath it. */
    public function descendantIds(int $id): array
    {
        $ids = [$id];

        foreach ($this->childrenByParent->get($id, collect()) as $child) {
            $ids = array_merge($ids, $this->descendantIds($child->id));
        }

        return $ids;
    }

    /**
     * Ids for one slug, or null when no slug was given.
     *
     * A slug that resolves to nothing returns [] - the caller turns that into
     * "matches nothing" rather than quietly dropping the filter and handing
     * back the whole shop.
     */
    public function idsForSlug(?string $slug): ?array
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $row = $this->rows->firstWhere('slug', $slug);

        return $row ? $this->descendantIds($row->id) : [];
    }

    /** @param array<int, string> $slugs */
    public function idsForSlugs(array $slugs): ?array
    {
        if ($slugs === []) {
            return null;
        }

        return $this->rows->whereIn('slug', $slugs)
            ->flatMap(fn ($c) => $this->descendantIds($c->id))
            ->unique()
            ->values()
            ->all();
    }
}
