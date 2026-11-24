<?php

/*
| One test (at least) per acceptance criterion, through the real HTTP stack:
| route → validation → controller → service → cache → Fake Store adapter (HTTP faked).
*/

beforeEach(fn () => fakeStore());

describe('Home page: GIVEN I have chosen 5 products to be "top rated"', function () {
    it('shows those 5 featured products, in the chosen order', function () {
        config(['catalogue.homepage' => ['limit' => 5, 'product_ids' => [11, 3, 5, 18, 12]]]);

        $this->getJson('/api/products/featured')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.*.id', [11, 3, 5, 18, 12])
            ->assertJsonStructure(['data' => [['id', 'title', 'description', 'price', 'image_url',
                'category' => ['slug', 'name'], 'rating' => ['rate', 'count']]]]);
    });

    it('skips a chosen product that no longer exists instead of failing', function () {
        config(['catalogue.homepage' => ['limit' => 5, 'product_ids' => [11, 999, 3]]]);

        $this->getJson('/api/products/featured')->assertOk()->assertJsonPath('data.*.id', [11, 3]);
    });

    it('respects the configured number of products', function () {
        config(['catalogue.homepage' => ['limit' => 2, 'product_ids' => [11, 3, 5]]]);

        $this->getJson('/api/products/featured')->assertOk()->assertJsonPath('data.*.id', [11, 3]);
    });
});

describe('Category page: WHEN I click on a category link', function () {
    it('lists the categories for the navigation', function () {
        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.*.slug', ['mens-clothing', 'jewelery', 'electronics', 'womens-clothing'])
            ->assertJsonPath('data.0.name', "Men's Clothing");
    });

    it('shows the curated top rated products and the other products in the category', function () {
        config(['catalogue.top_rated.categories.electronics' => ['limit' => 3, 'product_ids' => [12, 11, 9]]]);

        $response = $this->getJson('/api/categories/electronics/products')->assertOk();

        expect(ids($response->json('top_rated')))->toBe([12, 11, 9])
            ->and(ids($response->json('products.data')))->toBe([10, 13, 14])
            ->and($response->json('products.meta.total'))->toBe(3)
            ->and($response->json('category'))->toBe(['slug' => 'electronics', 'name' => 'Electronics']);
    });

    it('fills top rated by rating when fewer products are curated than the limit', function () {
        // jewelery ratings: 5 = 4.6, 6 = 3.9, 7 = 3.0, 8 = 1.9
        config(['catalogue.top_rated.categories.jewelery' => ['limit' => 2, 'product_ids' => [7]]]);

        $response = $this->getJson('/api/categories/jewelery/products')->assertOk();

        expect(ids($response->json('top_rated')))->toBe([7, 5])
            ->and(ids($response->json('products.data')))->toBe([6, 8]);
    });

    it('uses the default limit and rating order for a category that is not configured', function () {
        config(['catalogue.top_rated' => ['default_limit' => 2, 'categories' => []]]);

        // womens-clothing best rated: 18 (4.7), 19 (4.5)
        $response = $this->getJson('/api/categories/womens-clothing/products')->assertOk();

        expect(ids($response->json('top_rated')))->toBe([18, 19])
            ->and(ids($response->json('products.data')))->not->toContain(18, 19);
    });

    it('ignores a curated product that belongs to another category', function () {
        config(['catalogue.top_rated.categories.jewelery' => ['limit' => 2, 'product_ids' => [12, 6]]]);

        $response = $this->getJson('/api/categories/jewelery/products')->assertOk();

        expect(ids($response->json('top_rated')))->toBe([6, 5]);
    });

    it('paginates the other products', function () {
        config(['catalogue.top_rated.categories.electronics' => ['limit' => 1, 'product_ids' => [12]]]);

        $this->getJson('/api/categories/electronics/products?per_page=2&page=2')
            ->assertOk()
            ->assertJsonPath('products.data.*.id', [11, 13])
            ->assertJsonPath('products.meta', ['current_page' => 2, 'per_page' => 2, 'total' => 5, 'last_page' => 3]);
    });

    it('returns 404 for an unknown category', function () {
        $this->getJson('/api/categories/garden/products')->assertNotFound()->assertJsonStructure(['message']);
    });
});

describe('Search: WHEN I click the search button', function () {
    it('only returns products with text matching the search term', function () {
        $this->getJson('/api/products?search=jacket')
            ->assertOk()
            ->assertJsonPath('data.*.id', [3, 15, 16, 17])
            ->assertJsonPath('meta.total', 4);
    });

    it('does not match a word inside another word', function () {
        $response = $this->getJson('/api/products?search=mens%20cotton')->assertOk();

        expect(ids($response->json('data')))->toBe([3]); // not 20 ("Womens ... Cotton")
    });

    it('returns an empty list when nothing matches', function () {
        $this->getJson('/api/products?search=submarine')->assertOk()->assertJsonCount(0, 'data');
    });

    it('rejects an overly long search term', function () {
        $this->getJson('/api/products?search='.str_repeat('a', 101))
            ->assertUnprocessable()->assertJsonValidationErrors('search');
    });

    it('rejects a page size above the maximum', function () {
        $this->getJson('/api/products?per_page=500')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    });
});

describe('Product page: WHEN I click on a product tile', function () {
    it('shows the product details', function () {
        $this->getJson('/api/products/3')
            ->assertOk()
            ->assertJsonPath('data.id', 3)
            ->assertJsonPath('data.title', 'Mens Cotton Jacket')
            ->assertJsonPath('data.category.slug', 'mens-clothing');
    });

    it('returns 404 for an unknown product', function () {
        $this->getJson('/api/products/999')->assertNotFound();
    });
});

it('answers with 503 JSON (not a stack trace) when the product supplier is down', function () {
    fakeStoreDown();

    $this->getJson('/api/products/featured')
        ->assertStatus(503)
        ->assertHeader('Retry-After', '30')
        ->assertExactJson(['message' => 'The product catalogue is temporarily unavailable. Please try again shortly.']);
});

it('has a health endpoint', function () {
    $this->getJson('/api/health')->assertOk()->assertExactJson(['status' => 'ok']);
});
