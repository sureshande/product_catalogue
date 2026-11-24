<?php

namespace App\Contracts;

use App\Domain\Category;
use App\Domain\Paginated;
use App\Domain\Product;
use App\Domain\ProductQuery;
use App\Exceptions\ProductSourceUnavailable;

/**
 * The boundary between the catalogue and wherever product data lives.
 * Fake Store is one implementation; a database, PIM or search engine can be another.
 *
 * Every method throws ProductSourceUnavailable when the source cannot be reached.
 */
interface ProductSource
{
    /** @throws ProductSourceUnavailable */
    public function find(int $id): ?Product;

    /**
     * @param  int[]  $ids
     * @return Product[] in the order of $ids; unknown ids are left out
     *
     * @throws ProductSourceUnavailable
     */
    public function findMany(array $ids): array;

    /**
     * @return Category[]
     *
     * @throws ProductSourceUnavailable
     */
    public function categories(): array;

    /** @throws ProductSourceUnavailable */
    public function list(ProductQuery $query): Paginated;
}
