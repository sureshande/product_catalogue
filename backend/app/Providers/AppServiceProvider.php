<?php

namespace App\Providers;

use App\Adapters\CachedProductSource;
use App\Adapters\ConfigFeaturedProductRepository;
use App\Adapters\FakeStore\FakeStoreProductSource;
use App\Contracts\FeaturedProductRepository;
use App\Contracts\ProductSource;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The ONE place the product source is chosen. To change source, add a driver here.
        $this->app->scoped(ProductSource::class, function (Application $app) {
            $config = $app['config']['catalogue.source'];

            $source = match ($config['driver']) {
                'fakestore' => new FakeStoreProductSource(
                    http: $app->make(Http::class),
                    baseUrl: $config['base_url'],
                    timeout: $config['timeout'],
                    retries: $config['retries'],
                ),
                default => throw new InvalidArgumentException("Unknown catalogue source [{$config['driver']}]."),
            };

            return new CachedProductSource($source, $app['cache.store'], $config['cache_ttl'], $config['stale_ttl']);
        });

        $this->app->singleton(FeaturedProductRepository::class, fn (Application $app) => new ConfigFeaturedProductRepository(
            homepage: $app['config']['catalogue.homepage'] ?? [],
            topRated: $app['config']['catalogue.top_rated'] ?? [],
        ));
    }

    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('enquiries', fn (Request $request) => Limit::perMinute(
            config('catalogue.enquiries.rate_limit_per_minute'),
        )->by($request->ip()));
    }
}
