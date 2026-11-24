<?php

use App\Adapters\ConfigFeaturedProductRepository;

function repo(array $homepage = [], array $topRated = []): ConfigFeaturedProductRepository
{
    return new ConfigFeaturedProductRepository($homepage, $topRated);
}

it('returns home page ids in configured order, de-duplicated and capped at the limit', function () {
    $repo = repo(['limit' => 3, 'product_ids' => [7, 2, 7, 9, 4]]);

    expect($repo->homeProductIds())->toBe([7, 2, 9]);
});

it('ignores ids that are not positive integers', function () {
    $repo = repo(['limit' => 5, 'product_ids' => [3, 'abc', -1, 0, '8', null]]);

    expect($repo->homeProductIds())->toBe([3, 8]);
});

it('uses the per-category limit and ids', function () {
    $repo = repo(topRated: [
        'default_limit' => 3,
        'categories' => ['electronics' => ['limit' => 2, 'product_ids' => [12, 11]]],
    ]);

    expect($repo->categoryLimit('electronics'))->toBe(2)
        ->and($repo->categoryProductIds('electronics'))->toBe([12, 11]);
});

it('falls back to the default limit and no curated ids for an unlisted category', function () {
    $repo = repo(topRated: ['default_limit' => 4, 'categories' => []]);

    expect($repo->categoryLimit('womens-clothing'))->toBe(4)
        ->and($repo->categoryProductIds('womens-clothing'))->toBe([]);
});

it('falls back to a safe default when a configured limit is invalid', function (mixed $limit) {
    $repo = repo(topRated: ['default_limit' => 3, 'categories' => ['jewelery' => ['limit' => $limit]]]);

    expect($repo->categoryLimit('jewelery'))->toBe(3);
})->with([0, -1, 21, 'lots', null]);
