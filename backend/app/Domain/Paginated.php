<?php

namespace App\Domain;

final readonly class Paginated
{
    /** @param Product[] $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    /** @param Product[] $all */
    public static function slice(array $all, int $page, int $perPage): self
    {
        return new self(
            items: array_values(array_slice($all, ($page - 1) * $perPage, $perPage)),
            total: count($all),
            page: $page,
            perPage: $perPage,
        );
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function meta(): array
    {
        return [
            'current_page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'last_page' => $this->lastPage(),
        ];
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(fn (Product $p) => $p->toArray(), $this->items),
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            items: array_map(Product::fromArray(...), $data['items']),
            total: $data['total'],
            page: $data['page'],
            perPage: $data['per_page'],
        );
    }
}
