<?php

use App\Domain\Category;
use App\Domain\Product;

function productTitled(string $title, string $category = 'electronics'): Product
{
    return new Product(1, $title, 'A description', 10.0, Category::fromName($category), '', 4.0, 10);
}

it('matches words case-insensitively at the start of a word', function (string $title, string $term, bool $expected) {
    expect(productTitled($title)->matches($term))->toBe($expected);
})->with([
    'exact word' => ['Mens Cotton Jacket', 'jacket', true],
    'case-insensitive' => ['Mens Cotton Jacket', 'JACKET', true],
    'word prefix' => ['Mens Cotton Jacket', 'jack', true],
    'all words must match' => ['Mens Cotton Jacket', 'cotton jacket', true],
    'one word missing' => ['Mens Cotton Jacket', 'cotton shirt', false],
    'inside a word does not match' => ['Womens Rain Coat', 'mens', false],
    'apostrophes ignored' => ["Men's Coat", 'mens', true],
    'after a hyphen' => ['Slim Fit T-Shirts', 'shirt', true],
    'regex characters are literal' => ['SSD (1TB)', '(1tb', true],
]);

it('also searches the description and category name', function () {
    $product = new Product(1, 'Gold ring', 'Handmade in Bali', 10.0, Category::fromName('jewelery'), '', 4.0, 10);

    expect($product->matches('bali'))->toBeTrue()
        ->and($product->matches('jewelery'))->toBeTrue();
});
