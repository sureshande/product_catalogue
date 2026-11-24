<?php

/*
|--------------------------------------------------------------------------
| Product catalogue configuration
|--------------------------------------------------------------------------
|
| Merchandising (which products are "top rated" and how many) lives here so
| the owner can change it without touching code. It is read ONLY through
| App\Adapters\ConfigFeaturedProductRepository; replace that binding with a
| database-backed repository when an admin screen is needed.
|
*/

return [

    // Where product data comes from. Only "fakestore" exists in the PoC.
    'source' => [
        'driver' => env('CATALOGUE_SOURCE', 'fakestore'),
        'base_url' => env('FAKESTORE_BASE_URL', 'https://fakestoreapi.com'),
        'timeout' => (int) env('CATALOGUE_SOURCE_TIMEOUT', 5),     // seconds
        'retries' => (int) env('CATALOGUE_SOURCE_RETRIES', 2),
        'cache_ttl' => (int) env('CATALOGUE_CACHE_TTL', 600),       // seconds; 0 disables caching
        'stale_ttl' => (int) env('CATALOGUE_STALE_TTL', 86400),     // how long a stale copy may be served if the source is down
    ],

    // List 1 — Home page: "I have chosen 5 products to be top rated".
    // Array order = display order.
    'homepage' => [
        'limit' => (int) env('CATALOGUE_HOMEPAGE_LIMIT', 5),
        'product_ids' => [11, 3, 5, 18, 12],
    ],

    // List 2 — "Top rated" section on each category page, keyed by category slug.
    // If fewer product_ids than the limit are given, the rest is filled by highest rating.
    'top_rated' => [
        'default_limit' => (int) env('CATALOGUE_TOP_RATED_LIMIT', 3),
        'categories' => [
            'electronics' => ['limit' => 3, 'product_ids' => [12, 11, 9]],   // fully curated
            'jewelery' => ['limit' => 2, 'product_ids' => [5]],              // 1 curated + 1 by rating
            'mens-clothing' => ['limit' => 3, 'product_ids' => []],          // all by rating
            // 'womens-clothing' is not listed → default_limit, all by rating
        ],
    ],

    'pagination' => [
        'per_page' => 12,
        'max_per_page' => 50,
    ],

    'enquiries' => [
        'sales_inbox' => env('SALES_INBOX_EMAIL', 'sales@example.test'),
        'rate_limit_per_minute' => (int) env('ENQUIRY_RATE_LIMIT_PER_MINUTE', 5),
    ],

];
