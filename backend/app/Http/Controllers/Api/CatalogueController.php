<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListProductsRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Services\CatalogueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Read-only catalogue endpoints. Thin by design: validate, call the service, shape the response. */
class CatalogueController extends Controller
{
    public function __construct(private readonly CatalogueService $catalogue) {}

    /** GET /api/products/featured — home page "top rated". */
    public function featured(): AnonymousResourceCollection
    {
        return ProductResource::collection($this->catalogue->featured());
    }

    /** GET /api/categories */
    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->catalogue->categories());
    }

    /** GET /api/categories/{slug}/products — "top rated" + other products in the category. */
    public function category(string $slug, ListProductsRequest $request): JsonResponse
    {
        $page = $this->catalogue->category($slug, $request->page(), $request->perPage());

        return response()->json([
            'category' => new CategoryResource($page->category),
            'top_rated' => ProductResource::collection($page->topRated),
            'products' => ProductResource::paginated($page->others)->response()->getData(true),
        ]);
    }

    /** GET /api/products?search= — search, or browse everything without a term. */
    public function index(ListProductsRequest $request): AnonymousResourceCollection
    {
        return ProductResource::paginated(
            $this->catalogue->search($request->searchTerm(), $request->page(), $request->perPage()),
        );
    }

    /** GET /api/products/{id} */
    public function show(int $id): ProductResource
    {
        return new ProductResource($this->catalogue->product($id));
    }
}
