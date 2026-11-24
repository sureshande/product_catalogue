<?php

namespace App\Domain;

/**
 * Everything a list/search request can ask of a product source.
 * Designed so a database or search-engine source can answer it with one indexed query.
 */
final readonly class ProductQuery
{
    /** @param int[] $excludeIds */
    public function __construct(
        public ?string $category = null,
        public ?string $search = null,
        public array $excludeIds = [],
        public ProductSort $sort = ProductSort::Default,
        public int $page = 1,
        public int $perPage = 12,
    ) {}

    public function cacheKey(): string
    {
        return md5(json_encode([
            $this->category, $this->search, $this->excludeIds, $this->sort->value, $this->page, $this->perPage,
        ]));
    }
}
