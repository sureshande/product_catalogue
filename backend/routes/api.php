<?php

use App\Http\Controllers\Api\CatalogueController;
use App\Http\Controllers\Api\EnquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok']);

Route::get('/categories', [CatalogueController::class, 'categories']);
Route::get('/categories/{slug}/products', [CatalogueController::class, 'category'])
    ->where('slug', '[a-z0-9-]+');

Route::get('/products', [CatalogueController::class, 'index']);
Route::get('/products/featured', [CatalogueController::class, 'featured']);
Route::get('/products/{id}', [CatalogueController::class, 'show'])->whereNumber('id');

Route::post('/enquiries', [EnquiryController::class, 'store'])->middleware('throttle:enquiries');
