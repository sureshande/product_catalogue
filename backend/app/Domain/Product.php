<?php

namespace App\Domain;

/**
 * Our product shape. Every product source maps its own payload into this,
 * so nothing outside an adapter knows what the supplier's JSON looks like.
 */
final readonly class Product
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public float $price,
        public Category $category,
        public string $imageUrl,
        public float $ratingRate,
        public int $ratingCount,
    ) {}

    /**
     * True when every word of the term starts a word in the title, description or category.
     * Case-insensitive and apostrophe-insensitive: "mens" matches "Men's" but not "Women's",
     * "jack" matches "Jacket".
     */
    public function matches(string $term): bool
    {
        $haystack = self::normalise($this->title.' '.$this->description.' '.$this->category->name);

        foreach (preg_split('/\s+/u', self::normalise($term), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (! preg_match('/(?<![\p{L}\p{N}])'.preg_quote($word, '/').'/u', $haystack)) {
                return false;
            }
        }

        return true;
    }

    private static function normalise(string $text): string
    {
        return mb_strtolower(str_replace(["'", '’'], '', trim($text)));
    }

    /** Plain array form, used for caching (the cache store does not unserialize objects). */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'category' => $this->category->toArray(),
            'image_url' => $this->imageUrl,
            'rating_rate' => $this->ratingRate,
            'rating_count' => $this->ratingCount,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            description: $data['description'],
            price: $data['price'],
            category: Category::fromArray($data['category']),
            imageUrl: $data['image_url'],
            ratingRate: $data['rating_rate'],
            ratingCount: $data['rating_count'],
        );
    }
}
