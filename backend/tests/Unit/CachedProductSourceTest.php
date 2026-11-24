<?php

use App\Adapters\CachedProductSource;
use App\Adapters\FakeStore\FakeStoreProductSource;
use App\Domain\ProductQuery;
use App\Exceptions\ProductSourceUnavailable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

/** A fresh inner source per call mimics a new request (the inner source memoises per instance). */
function cached(Repository $cache, int $ttl = 600): CachedProductSource
{
    $inner = new FakeStoreProductSource(app(Factory::class), 'https://fakestoreapi.com', timeout: 5, retries: 0);

    return new CachedProductSource($inner, $cache, $ttl, staleTtl: 86400);
}

beforeEach(function () {
    $this->cache = new Repository(new ArrayStore(serializesValues: true));
});

it('returns the same results as the source it wraps', function () {
    fakeStore();

    $product = cached($this->cache)->find(3);
    $page = cached($this->cache)->list(new ProductQuery(category: 'jewelery'));

    expect($product->title)->toBe('Mens Cotton Jacket')
        ->and($product->category->slug)->toBe('mens-clothing')
        ->and(array_map(fn ($p) => $p->id, $page->items))->toBe([5, 6, 7, 8]);
});

it('serves repeat requests from cache without calling the supplier', function () {
    fakeStore();

    cached($this->cache)->find(3);
    cached($this->cache)->find(3);
    cached($this->cache)->categories();
    cached($this->cache)->categories();

    Http::assertSentCount(2); // one per distinct question, not per request
});

it('caches "not found" too', function () {
    fakeStore();

    expect(cached($this->cache)->find(999))->toBeNull()
        ->and(cached($this->cache)->find(999))->toBeNull();
    Http::assertSentCount(1);
});

it('serves the stale copy when the supplier is down after the fresh copy expired', function () {
    fakeStore();
    cached($this->cache)->find(3);

    $this->travel(11)->minutes();   // fresh copy (10 min) expired, stale copy (24h) still there
    fakeStoreDown();

    expect(cached($this->cache)->find(3)->title)->toBe('Mens Cotton Jacket');
});

it('throws when the supplier is down and there is nothing cached', function () {
    fakeStoreDown();

    cached($this->cache)->find(3);
})->throws(ProductSourceUnavailable::class);

it('does not cache when the TTL is 0', function () {
    fakeStore();

    cached($this->cache, ttl: 0)->find(3);
    cached($this->cache, ttl: 0)->find(3);

    Http::assertSentCount(2);
});
