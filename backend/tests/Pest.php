<?php

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
| Every test boots the app and is forbidden from making real HTTP calls,
| so the suite is fast, deterministic and works offline.
*/
pest()->extend(TestCase::class)
    ->beforeEach(fn () => Http::preventStrayRequests())
    ->in('Feature', 'Unit');

/** Fake Store responds with the real catalogue captured in tests/Fixtures. */
function fakeStore(): void
{
    Http::fake([
        'fakestoreapi.com/products' => Http::response(fakeStoreProducts()),
    ]);
}

/**
 * Fake Store is down. Swaps in a fresh HTTP factory first: Http::fake() appends stubs and the
 * first match wins, so without this an earlier fakeStore() would keep answering 200.
 */
function fakeStoreDown(int $status = 503): void
{
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(['fakestoreapi.com/*' => Http::response('Service Unavailable', $status)]);
}

function fakeStoreProducts(): array
{
    return json_decode(file_get_contents(__DIR__.'/Fixtures/fakestore-products.json'), true);
}

/** IDs from a JSON list of products. */
function ids(array $products): array
{
    return array_column($products, 'id');
}
