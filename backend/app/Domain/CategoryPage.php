<?php

namespace App\Domain;

final readonly class CategoryPage
{
    /** @param Product[] $topRated */
    public function __construct(
        public Category $category,
        public array $topRated,
        public Paginated $others,
    ) {}
}
