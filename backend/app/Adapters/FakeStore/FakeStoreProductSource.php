<?php

namespace App\Adapters\FakeStore;

use App\Contracts\ProductSource;
use App\Domain\Category;
use App\Domain\Paginated;
use App\Domain\Product;
use App\Domain\ProductQuery;
use App\Domain\ProductSort;
use App\Exceptions\ProductSourceUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * Reads products from the Fake Store API (https://fakestoreapi.com).
 *
 * Fake Store has 20 products and no search, filtering or paging, so this adapter
 * loads the whole catalogue in one call and answers queries in memory.
 * A database or search-engine source would answer the same ProductQuery with an indexed query.
 */
class FakeStoreProductSource implements ProductSource
{
    /** @var Product[]|null keyed by id, loaded once per instance */
    private ?array $products = null;

    public function __construct(
        private readonly Http $http,
        private readonly string $baseUrl,
        private readonly int $timeout,
        private readonly int $retries,
    ) {}

    public function find(int $id): ?Product
    {
        return $this->all()[$id] ?? null;
    }

    public function findMany(array $ids): array
    {
        $all = $this->all();

        return array_values(array_filter(array_map(fn (int $id) => $all[$id] ?? null, $ids)));
    }

    public function categories(): array
    {
        $categories = [];
        foreach ($this->all() as $product) {
            $categories[$product->category->slug] ??= $product->category;
        }

        return array_values($categories);
    }

    public function list(ProductQuery $query): Paginated
    {
        $products = array_filter($this->all(), function (Product $p) use ($query) {
            return ($query->category === null || $p->category->slug === $query->category)
                && ($query->search === null || $p->matches($query->search))
                && ! in_array($p->id, $query->excludeIds, true);
        });

        if ($query->sort === ProductSort::Rating) {
            usort($products, fn (Product $a, Product $b) => [$b->ratingRate, $b->ratingCount, $a->id]
                <=> [$a->ratingRate, $a->ratingCount, $b->id]);
        }

        return Paginated::slice(array_values($products), $query->page, $query->perPage);
    }

    /** @return Product[] keyed by id */
    private function all(): array
    {
        return $this->products ??= $this->fetchAll();
    }

    /** @return Product[] keyed by id */
    private function fetchAll(): array
    {
        try {
            $response = $this->http->baseUrl($this->baseUrl)
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry($this->retries + 1, 200, fn ($e) => $e instanceof ConnectionException
                    || ($e instanceof RequestException && $e->response->serverError()), throw: false)
                ->get('/products');
        } catch (ConnectionException $e) {
            throw new ProductSourceUnavailable('Fake Store unreachable: '.$e->getMessage(), previous: $e);
        }

        $rows = $response->json();
        if ($response->failed() || ! is_array($rows)) {
            throw new ProductSourceUnavailable("Fake Store returned HTTP {$response->status()} or an invalid body.");
        }

        $products = [];
        foreach ($rows as $row) {
            $product = $this->map($row);
            if ($product !== null) {
                $products[$product->id] = $product;
            }
        }

        return $products;
    }

    /** Maps one Fake Store row; skips (and logs) rows missing required fields instead of failing the page. */
    private function map(mixed $row): ?Product
    {
        if (! is_array($row) || ! is_int($row['id'] ?? null) || ! is_string($row['title'] ?? null)
            || ! is_numeric($row['price'] ?? null) || ! is_string($row['category'] ?? null)) {
            Log::warning('catalogue.fakestore.malformed_product_skipped', ['row' => $row]);

            return null;
        }

        return new Product(
            id: $row['id'],
            title: $row['title'],
            description: (string) ($row['description'] ?? ''),
            price: round((float) $row['price'], 2),
            category: Category::fromName($row['category']),
            imageUrl: (string) ($row['image'] ?? ''),
            ratingRate: (float) ($row['rating']['rate'] ?? 0),
            ratingCount: (int) ($row['rating']['count'] ?? 0),
        );
    }
}
