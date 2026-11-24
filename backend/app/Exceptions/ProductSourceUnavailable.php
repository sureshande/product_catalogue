<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** The product source could not be reached or returned something unusable. */
class ProductSourceUnavailable extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'The product catalogue is temporarily unavailable. Please try again shortly.',
        ], 503, ['Retry-After' => '30']);
    }
}
