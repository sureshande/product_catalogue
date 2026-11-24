<?php

namespace App\Adapters;

use App\Contracts\ProductSource;
use App\Domain\Category;
use App\Domain\Paginated;
use App\Domain\Product;
use App\Domain\ProductQuery;
use App\Exceptions\ProductSourceUnavailable;
use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Log;

/**
 * Wraps any ProductSource with a cache, so the upstream is not called on every request.
 *
 * Each result is stored twice: a fresh copy (short TTL) and a stale copy (long TTL).
 * If the source is down after the fresh copy expires, the stale copy is served
 * instead of an error. Values are stored as plain arrays because the cache store
 * is configured not to unserialize objects.
 */
class CachedProductSource implements ProductSource
{
    private const PREFIX = 'catalogue:';

    public function __construct(
        private readonly ProductSource $inner,
        private readonly Cache $cache,
        private readonly int $ttl,
        private readonly int $staleTtl,
    ) {}

    public function find(int $id): ?Product
    {
        $data = $this->remember("find:{$id}", fn () => $this->inner->find($id)?->toArray());

        return $data === null ? null : Product::fromArray($data);
    }

    public function findMany(array $ids): array
    {
        $data = $this->remember('many:'.implode(',', $ids), fn () => array_map(
            fn (Product $p) => $p->toArray(), $this->inner->findMany($ids),
        ));

        return array_map(Product::fromArray(...), $data);
    }

    public function categories(): array
    {
        $data = $this->remember('categories', fn () => array_map(
            fn (Category $c) => $c->toArray(), $this->inner->categories(),
        ));

        return array_map(Category::fromArray(...), $data);
    }

    public function list(ProductQuery $query): Paginated
    {
        return Paginated::fromArray(
            $this->remember('list:'.$query->cacheKey(), fn () => $this->inner->list($query)->toArray()),
        );
    }

    private function remember(string $key, Closure $fetch): mixed
    {
        $key = self::PREFIX.$key;

        if ($this->ttl > 0 && is_array($hit = $this->cache->get($key))) {
            return $hit['value'];
        }

        try {
            $value = $fetch();
        } catch (ProductSourceUnavailable $e) {
            $stale = $this->cache->get("{$key}:stale");
            if (! is_array($stale)) {
                throw $e;
            }
            Log::warning('catalogue.serving_stale', ['key' => $key, 'reason' => $e->getMessage()]);

            return $stale['value'];
        }

        // Wrapped in an array so a cached null ("product not found") is distinguishable from a miss.
        if ($this->ttl > 0) {
            $this->cache->put($key, ['value' => $value], $this->ttl);
            $this->cache->put("{$key}:stale", ['value' => $value], $this->staleTtl);
        }

        return $value;
    }
}
