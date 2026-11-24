<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** A product or category the client asked for does not exist. */
class NotFound extends RuntimeException
{
    public static function product(int $id): self
    {
        return new self("Product {$id} was not found.");
    }

    public static function category(string $slug): self
    {
        return new self("Category '{$slug}' was not found.");
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 404);
    }
}
