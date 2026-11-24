<?php

namespace App\Http\Resources;

use App\Domain\Paginated;
use App\Domain\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public product contract the client depends on. Changing the product
 * source must never change this shape.
 *
 * @property Product $resource
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'price' => $this->resource->price,
            'image_url' => $this->resource->imageUrl,
            'category' => new CategoryResource($this->resource->category),
            'rating' => [
                'rate' => $this->resource->ratingRate,
                'count' => $this->resource->ratingCount,
            ],
        ];
    }

    /** {"data": [...], "meta": {current_page, per_page, total, last_page}} */
    public static function paginated(Paginated $page): AnonymousResourceCollection
    {
        return self::collection($page->items)->additional(['meta' => $page->meta()]);
    }
}
