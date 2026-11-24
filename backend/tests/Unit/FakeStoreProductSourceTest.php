<?php

use App\Adapters\FakeStore\FakeStoreProductSource;
use App\Domain\ProductQuery;
use App\Domain\ProductSort;
use App\Exceptions\ProductSourceUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

function source(): FakeStoreProductSource
{
    return new FakeStoreProductSource(app(Factory::class), 'https://fakestoreapi.com', timeout: 5, retries: 0);
}

it('maps the supplier payload into our Product shape', function () {
    fakeStore();

    $product = source()->find(3);

    expect($product->id)->toBe(3)
        ->and($product->title)->toBe('Mens Cotton Jacket')
        ->and($product->price)->toBe(55.99)
        ->and($product->category->slug)->toBe('mens-clothing')
        ->and($product->category->name)->toBe("Men's Clothing")
        ->and($product->ratingRate)->toBe(4.7)
        ->and($product->ratingCount)->toBe(500)
        ->and($product->imageUrl)->toStartWith('https://');
});

it('loads the catalogue with a single upstream call per instance', function () {
    fakeStore();
    $source = source();

    $source->find(1);
    $source->categories();
    $source->list(new ProductQuery);

    Http::assertSentCount(1);
});

it('returns findMany in the requested order and skips unknown ids', function () {
    fakeStore();

    expect(array_map(fn ($p) => $p->id, source()->findMany([12, 999, 3, 5])))->toBe([12, 3, 5]);
});

it('derives categories from the products', function () {
    fakeStore();

    expect(array_map(fn ($c) => $c->slug, source()->categories()))
        ->toBe(['mens-clothing', 'jewelery', 'electronics', 'womens-clothing']);
});

it('filters by category, excludes ids, sorts by rating and paginates', function () {
    fakeStore();

    $page = source()->list(new ProductQuery(
        category: 'electronics', excludeIds: [11], sort: ProductSort::Rating, page: 1, perPage: 3,
    ));

    // electronics by rating: 12 (4.8/400), 11 (4.8/319, excluded), 9 (3.3), 10 (2.9/470), 13 (2.9/250), 14
    expect(array_map(fn ($p) => $p->id, $page->items))->toBe([12, 9, 10])
        ->and($page->total)->toBe(5)
        ->and($page->lastPage())->toBe(2);
});

it('skips malformed rows instead of failing the whole catalogue', function () {
    Http::fake(['fakestoreapi.com/products' => Http::response([
        ['id' => 1, 'title' => 'Good', 'price' => 10, 'category' => 'electronics'],
        ['title' => 'No id'],
        'not even an object',
    ])]);

    $page = source()->list(new ProductQuery);

    expect($page->total)->toBe(1)->and($page->items[0]->description)->toBe('');
});

it('throws ProductSourceUnavailable when the supplier errors', function () {
    fakeStoreDown(500);

    source()->find(1);
})->throws(ProductSourceUnavailable::class);

it('throws ProductSourceUnavailable when the supplier returns something that is not a list', function () {
    Http::fake(['fakestoreapi.com/products' => Http::response('<html>maintenance</html>')]);

    source()->find(1);
})->throws(ProductSourceUnavailable::class);

it('throws ProductSourceUnavailable when the supplier cannot be reached', function () {
    Http::fake(fn () => throw new ConnectionException('timed out'));

    source()->find(1);
})->throws(ProductSourceUnavailable::class);
