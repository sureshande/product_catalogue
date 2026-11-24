<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Paging (and optional search term) for any product list endpoint. */
class ListProductsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('catalogue.pagination.max_per_page')],
        ];
    }

    public function searchTerm(): ?string
    {
        $term = $this->validated('search');

        return is_string($term) && trim($term) !== '' ? trim($term) : null;
    }

    public function page(): int
    {
        return (int) ($this->validated('page') ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? config('catalogue.pagination.per_page'));
    }
}
