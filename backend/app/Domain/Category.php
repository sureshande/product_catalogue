<?php

namespace App\Domain;

use Illuminate\Support\Str;

final readonly class Category
{
    public function __construct(
        public string $slug,
        public string $name,
    ) {}

    /** "men's clothing" → slug "mens-clothing", name "Men's Clothing". */
    public static function fromName(string $name): self
    {
        return new self(Str::slug($name), Str::title($name));
    }

    public function toArray(): array
    {
        return ['slug' => $this->slug, 'name' => $this->name];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['slug'], $data['name']);
    }
}
