<?php

namespace App\Contracts;

/**
 * Merchandising choices made by the business: which products are "top rated".
 * Two independent lists — one for the home page, one per category.
 */
interface FeaturedProductRepository
{
    /** @return int[] ordered, de-duplicated, capped at the home page limit */
    public function homeProductIds(): array;

    /** @return int[] curated ids for the category in display order ([] = fill by rating) */
    public function categoryProductIds(string $categorySlug): array;

    /** Number of products in the category's "Top rated" section. */
    public function categoryLimit(string $categorySlug): int;
}
