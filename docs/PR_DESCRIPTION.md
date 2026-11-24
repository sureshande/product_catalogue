# Product catalogue PoC: browse, search, enquire

## What this does

A React + TypeScript client and a Laravel 13 API that put a product catalogue on the Fake Store API.
Customers see curated "top rated" products, browse by category, search, view details and send an
enquiry, which is saved and then emailed to the sales inbox through a queue.

The priority was **boundaries that let the PoC grow**, not feature count:

- **Product data** is only reached through `ProductSource` (Fake Store adapter + cache decorator). Changing source is a new class and one line.
- **Merchandising** ("top rated", how many and which) is backend config behind `FeaturedProductRepository`, in two independent lists (home page and per category).
- **Look & feel** is Bootstrap 5 with one token file.
- **Email** is a Laravel Mailable sent by a queued job; the provider is an env setting.

## Acceptance criteria → tests

| Criterion | Backend (Pest) | Frontend (Vitest) |
|---|---|---|
| Home: chosen 5 top rated | `CatalogueApiTest` › Home page (order, limit, missing id skipped) | `catalogue.test` › Home page |
| Category: top rated + additional | `CatalogueApiTest` › Category page (curated, fill by rating, wrong-category ignored, pagination, 404) | `catalogue.test` › Category page |
| Search: only matching products | `CatalogueApiTest` › Search, `ProductSearchTest` (word-start matching dataset) | `catalogue.test` › Search |
| Product page | `CatalogueApiTest` › Product page (200 / 404) | `catalogue.test` › Product page |
| Enquiry: email to sales + "Thank you" | `EnquiryApiTest` (202, mail to inbox with reply-to, snapshot from our catalogue, validation dataset, unknown product, rate limit, job retry/failed) | `enquiry.test` (submit → Thank you, 422 field errors, 429, Escape) |
| Supplier down | `CatalogueApiTest` 503 JSON, `CachedProductSourceTest` stale fallback | `catalogue.test` error + retry |

The CI Docker job also boots the whole stack, submits an enquiry, and checks that Mailpit received it.

## How to review

```bash
docker compose up --build    # app :8080, sales inbox :8025
```

Suggested reading order: `backend/app/Contracts` → `Services/CatalogueService.php` →
`Adapters/FakeStore/FakeStoreProductSource.php` → `config/catalogue.php` → `frontend/src/api/client.ts` → `frontend/src/pages`.

## Decisions worth challenging

- **Config, not a database, for top-rated lists:** there's no admin UI yet, so config is reviewable and testable. The interface allows a database later.
- **Two lists, not one list with a `show_in_homepage` flag:** the flag needed hidden cross-list rules.
- **Search matches the start of words:** substring matching returned women's products for "mens".
- **202 plus async email:** the enquiry is persisted first, so a mail outage loses nothing.
- **The client never sees Fake Store's shape:** API Resources are the contract.

Full list: [DECISIONS.md](../DECISIONS.md). AI usage, and what it got wrong: [AI_USAGE.md](../AI_USAGE.md).

## Not in this PR

Admin UI, database product source, Playwright E2E, PHPStan, IaC. See "With another day" in DECISIONS.md.
