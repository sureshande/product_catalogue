<?php

namespace App\Services;

use App\Contracts\FeaturedProductRepository;
use App\Contracts\ProductSource;
use App\Domain\Category;
use App\Domain\CategoryPage;
use App\Domain\Paginated;
use App\Domain\Product;
use App\Domain\ProductQuery;
use App\Domain\ProductSort;
use App\Exceptions\NotFound;
use Illuminate\Support\Facades\Log;

/**
 * All catalogue business rules. Controllers call this; it never knows which product source is behind it.
 */
class CatalogueService
{
    public function __construct(
        private readonly ProductSource $products,
        private readonly FeaturedProductRepository $featured,
    ) {}

    /** Home page: the products the owner chose, in their order. */
    public function featured(): array
    {
        $ids = $this->featured->homeProductIds();
        $products = $this->products->findMany($ids);

        $this->logMissing('homepage', $ids, $products);

        return $products;
    }

    /** @return Category[] */
    public function categories(): array
    {
        return $this->products->categories();
    }

    /**
     * Category page: curated "top rated" first, filled up to the limit by rating,
     * then every other product in the category, paginated and never repeated.
     */
    public function category(string $slug, int $page, int $perPage): CategoryPage
    {
        $category = $this->findCategory($slug);
        $limit = $this->featured->categoryLimit($slug);

        $curatedIds = $this->featured->categoryProductIds($slug);
        $curated = array_values(array_filter(
            $this->products->findMany($curatedIds),
            fn (Product $p) => $p->category->slug === $slug,
        ));
        $this->logMissing("top_rated.{$slug}", $curatedIds, $curated);
        $topRated = array_slice($curated, 0, $limit);

        $missing = $limit - count($topRated);
        if ($missing > 0) {
            $fill = $this->products->list(new ProductQuery(
                category: $slug,
                excludeIds: $this->ids($topRated),
                sort: ProductSort::Rating,
                perPage: $missing,
            ));
            $topRated = [...$topRated, ...$fill->items];
        }

        $others = $this->products->list(new ProductQuery(
            category: $slug,
            excludeIds: $this->ids($topRated),
            page: $page,
            perPage: $perPage,
        ));

        return new CategoryPage($category, $topRated, $others);
    }

    /** Search (or browse everything when $term is null). */
    public function search(?string $term, int $page, int $perPage): Paginated
    {
        return $this->products->list(new ProductQuery(search: $term, page: $page, perPage: $perPage));
    }

    public function product(int $id): Product
    {
        return $this->products->find($id) ?? throw NotFound::product($id);
    }

    private function findCategory(string $slug): Category
    {
        foreach ($this->products->categories() as $category) {
            if ($category->slug === $slug) {
                return $category;
            }
        }

        throw NotFound::category($slug);
    }

    /** @param Product[] $products @return int[] */
    private function ids(array $products): array
    {
        return array_map(fn (Product $p) => $p->id, $products);
    }

    /** Configured ids that no longer exist (or are in the wrong category) are skipped, not fatal. */
    private function logMissing(string $list, array $wanted, array $found): void
    {
        $skipped = array_values(array_diff($wanted, $this->ids($found)));

        if ($skipped !== []) {
            Log::warning('catalogue.curated_products_skipped', ['list' => $list, 'ids' => $skipped]);
        }
    }
}
