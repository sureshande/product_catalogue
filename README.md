# Product Catalogue — PoC

A product catalogue where customers browse, search and send an enquiry about a product.
Built as a **foundation that can grow**: the product data source, look & feel and email provider are each
behind one seam and can be swapped without rewriting the rest.

| | |
|---|---|
| **Client** | React 18 + TypeScript + Vite, Bootstrap 5 |
| **Backend** | PHP 8.4 / Laravel 13, JSON API |
| **Data** | Fake Store API (products), MySQL (enquiries, queue, cache) |
| **Email** | Queued; Mailpit locally, SES in AWS |

**Why this stack:** Laravel ships validation, queues, mail, caching, rate limiting and HTTP fakes as
first-party features, so this PoC needs very little custom infrastructure code. It is also the stack I know
best, which matters when I have to defend every line. React + TypeScript is a web client, so a reviewer
runs it with one command and doesn't need a simulator. The typed API contract catches client/server drift.

More detail: [DECISIONS.md](DECISIONS.md) (assumptions and trade-offs) · [AI_USAGE.md](AI_USAGE.md) ·
[docs/PLAN.md](docs/PLAN.md) (architecture and flow diagrams).

---

## Run it (≈ 5 minutes, needs Docker only)

```bash
git clone <this repo> && cd product-catalogue
docker compose up --build
```

| What | URL |
|---|---|
| **The app** | http://localhost:8080 |
| **Sales inbox** (Mailpit: enquiry emails arrive here) | http://localhost:8025 |
| API health | http://localhost:8080/api/health |

There is no `.env` to create; everything has a working default. To change a setting, `cp .env.example .env`
and edit it. Stop with `Ctrl+C`; reset the database with `docker compose down -v`.

Try the acceptance criteria:
1. **Home**: the 5 "top rated" products chosen in config.
2. **Category**: click *Electronics* in the sidebar for its top rated products plus more products in that category.
3. **Search**: type `jacket`, click **Search**.
4. **Product**: click any tile.
5. **Enquiry**: on a product, click **Enquire**, fill in the form, and you get a *Thank you*. Open
   http://localhost:8025 to see the email in the sales inbox.

## Run the tests

Tests need no network and no database server.

```bash
# Backend (PHP 8.3+ with pdo_sqlite)
cd backend && composer install && ./vendor/bin/pest

# Frontend (Node 18+)
cd frontend && npm ci && npm test
```

CI (`.github/workflows/ci.yml`) runs both suites, Pint, TypeScript, ESLint, and a Docker smoke test that
submits an enquiry and checks it reached Mailpit.

## Run without Docker (development)

```bash
# backend — needs MySQL (or set DB_CONNECTION=sqlite) and, for email, Mailpit on :1025
cd backend && cp .env.example .env && composer install && php artisan key:generate
php artisan migrate && php artisan serve          # http://localhost:8000
php artisan queue:work                             # in a second terminal: sends enquiry emails

# frontend — proxies /api to :8000
cd frontend && npm ci && npm run dev               # http://localhost:5173
```

---

## Configuring "top rated" products

Merchandising is backend config, in two independent lists: one for the home page and one per category.
Edit [`backend/config/catalogue.php`](backend/config/catalogue.php):

```php
'homepage' => [
    'limit'       => 5,                       // how many on the home page
    'product_ids' => [11, 3, 5, 18, 12],      // which ones, in display order
],

'top_rated' => [
    'default_limit' => 3,                     // for categories not listed below
    'categories' => [
        'electronics'   => ['limit' => 3, 'product_ids' => [12, 11, 9]],  // fully curated
        'jewelery'      => ['limit' => 2, 'product_ids' => [5]],          // 1 chosen + 1 by rating
        'mens-clothing' => ['limit' => 3, 'product_ids' => []],           // all by rating
    ],
],
```

- If fewer products are chosen than the `limit`, the rest is filled by highest rating.
- Chosen products that don't exist or belong to another category are skipped and logged, never an error.
- Limits are clamped to 1–20. Counts can also be set via env (`CATALOGUE_HOMEPAGE_LIMIT`, `CATALOGUE_TOP_RATED_LIMIT`).
- Both lists sit behind `FeaturedProductRepository`, so an admin screen backed by database tables can replace config later.

Other settings (timeouts, cache TTL, sales inbox, rate limit) are listed in
[`backend/.env.example`](backend/.env.example).

## API

| Method | Endpoint | |
|---|---|---|
| GET | `/api/products/featured` | Home page top rated |
| GET | `/api/categories` | Category navigation |
| GET | `/api/categories/{slug}/products?page=&per_page=` | `top_rated` + paginated `products` |
| GET | `/api/products?search=&page=&per_page=` | Search (or browse all) |
| GET | `/api/products/{id}` | Product details |
| POST | `/api/enquiries` | `{product_id, name, email, phone?, message?}` → **202** |
| GET | `/api/health` | Liveness |

Errors are always JSON `{message, errors?}`: **404** unknown product or category, **422** validation,
**429** rate limited, **503** product supplier down (the last good copy is served from cache when it can be).

## Project structure

```
backend/                  Laravel API
  app/Contracts/          ProductSource, FeaturedProductRepository   ← the seams
  app/Adapters/           FakeStoreProductSource, CachedProductSource, ConfigFeaturedProductRepository
  app/Domain/             Product, Category, ProductQuery, Paginated (readonly DTOs)
  app/Services/           CatalogueService, EnquiryService            ← business rules
  app/Http/               thin controllers, FormRequests, Resources (public contract)
  app/Jobs, app/Mail      SendEnquiryEmail (queued, retried), EnquiryReceived
  config/catalogue.php    merchandising + source settings
  tests/                  Unit + Feature (Pest), Fixtures/fakestore-products.json
frontend/                 React SPA
  src/api/                client.ts (only place that knows the backend), types.ts
  src/layout, pages, components
  src/theme/tokens.scss   look & feel swap point
  src/test/               Vitest + Testing Library + MSW
docker-compose.yml        mysql · api · worker · web (nginx) · mailpit
docs/PLAN.md              architecture, flow diagrams, scale path, AWS target
```
