<?php

namespace App\Adapters;

use App\Contracts\FeaturedProductRepository;
use Illuminate\Support\Facades\Log;

/**
 * Reads the merchandising lists from config/catalogue.php.
 * This is the only class that reads the "homepage" and "top_rated" config keys.
 */
class ConfigFeaturedProductRepository implements FeaturedProductRepository
{
    private const MIN_LIMIT = 1;

    private const MAX_LIMIT = 20;

    private const FALLBACK_LIMIT = 3;

    public function __construct(
        private readonly array $homepage,
        private readonly array $topRated,
    ) {}

    public function homeProductIds(): array
    {
        $limit = $this->limit($this->homepage['limit'] ?? null, 5, 'homepage');

        return array_slice($this->ids($this->homepage['product_ids'] ?? []), 0, $limit);
    }

    public function categoryProductIds(string $categorySlug): array
    {
        return $this->ids($this->topRated['categories'][$categorySlug]['product_ids'] ?? []);
    }

    public function categoryLimit(string $categorySlug): int
    {
        $default = $this->limit($this->topRated['default_limit'] ?? null, self::FALLBACK_LIMIT, 'top_rated.default_limit');

        return $this->limit($this->topRated['categories'][$categorySlug]['limit'] ?? $default, $default, "top_rated.{$categorySlug}");
    }

    /** Positive ints only, duplicates removed, configured order kept. */
    private function ids(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        $clean = array_filter(array_map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT), $ids), fn ($id) => $id > 0);

        return array_values(array_unique($clean));
    }

    private function limit(mixed $value, int $default, string $setting): int
    {
        $limit = filter_var($value, FILTER_VALIDATE_INT);

        if ($limit === false || $limit < self::MIN_LIMIT || $limit > self::MAX_LIMIT) {
            Log::warning('catalogue.invalid_limit', ['setting' => $setting, 'value' => $value, 'using' => $default]);

            return $default;
        }

        return $limit;
    }
}
