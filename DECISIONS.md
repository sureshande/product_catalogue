# Decisions & assumptions

## Assumptions (where the acceptance criteria left gaps)

| # | Criterion | Assumption |
|---|---|---|
| 1 | Home: *"I have chosen 5 products to be top rated"* | The owner **curates** them; they are not derived from the supplier's rating. The IDs and the count live in backend config (`config/catalogue.php` → `homepage`). |
| 2 | Category: *"top rated products for that category"* | A separate list per category in config: `limit` (how many) and `product_ids` (which, in order). If fewer are chosen than the limit, the rest is filled by highest `rating.rate`, with ties broken by `rating.count`. |
| 3 | Category: *"additional products in that category"* | Every other product in the category, paginated, **never repeating** one already shown in Top rated. |
| 4 | Categories | The supplier's own categories (Fake Store: electronics, jewelery, men's clothing, women's clothing), exposed as URL-safe slugs (`mens-clothing`). No separate business taxonomy yet. |
| 5 | Search: *"only see products that have text matching that search term"* | Matches the title, description and category name, case-insensitive. **Every word** of the term must match, at the **start of a word**. So `mens` matches "Men's" but not "Women's", and `jack` matches "Jacket". An empty term doesn't search (the button is disabled). |
| 6 | Enquiry: *"complete the form with my contact details"* | Name and email are required; phone and message are optional. One product per enquiry. |
| 7 | Enquiry: *"the sales inbox will receive an email"* | The inbox address is config (`SALES_INBOX_EMAIL`). The email is sent **asynchronously** after the enquiry is saved. The customer sees "Thank you" once it's saved (HTTP 202), not when the mail server accepts it. |
| 8 | General | No customer accounts or auth, a single currency (USD as supplied), no stock, cart or checkout. |
| 9 | Product details | The product name and price in the email come from **our** catalogue lookup, never from the client. |

## Dropped or simplified (and why)

| What | Decision | Why |
|---|---|---|
| Admin UI for top-rated products | Config file | Not in the criteria. Config is versioned, reviewable and testable. `FeaturedProductRepository` is the seam for a database and admin screen. The cost: a change needs a deploy or restart. |
| Real search engine | In-memory filtering inside the Fake Store adapter | Fake Store has 20 products and no search API. The `ProductQuery` contract (category, search, exclude, sort, page) is what a database or OpenSearch adapter would implement with an indexed query. |
| Final visual design | A neutral placeholder theme: Bootstrap 5 + Bootstrap Icons (both MIT) and the system font stack, with all colours in one token file | The creative agency is still working on it. Re-branding means editing `tokens.scss`, not components. Nothing is copied from any company's product, logo, fonts or CSS, so there are no licensing or IP concerns. |
| Production web server | `php artisan serve` with 4 workers in Docker | Fine for local use. The AWS target is php-fpm + nginx (or Octane) on ECS; see `docs/PLAN.md`. |
| Infrastructure as code | Documented, not built | The time-box. The constraint is only "AWS-compatible": MySQL→RDS, database queue→SQS, Mailpit→SES, and Redis/ElastiCache for cache are all config changes. |
| Browser E2E (Playwright) | Not done | The critical path is covered by API feature tests, frontend integration tests through the full route tree, and the CI Docker smoke test (which submits a real enquiry and checks Mailpit). |
| Honeypot behaviour | Returns 422 if filled | Simplest to implement and test. Silently accepting and dropping bot submissions would be better UX against bots. |
| Static analysis (PHPStan/Larastan) | Not added | The time-box; Pint, TypeScript strict and ESLint are in CI. |

## How the design grows beyond the PoC

The three things the brief says will change are each **one seam**:

| Change | What you touch | What stays untouched |
|---|---|---|
| **Product data source** (PIM, database, a different supplier) | A new `ProductSource` implementation and one `match` arm in `AppServiceProvider` | Services, controllers, API contract, React app, all feature tests |
| **Look & feel** | `frontend/src/theme/tokens.scss` (colours, fonts, radius); presentational components if layout changes | Data fetching, API client, routing |
| **Email provider** | `MAIL_MAILER=ses` (or postmark, etc.) in env | All code |
| **Email content / subject** | The Blade file `resources/views/mail/enquiry.blade.php` (or `ENQUIRY_EMAIL_TEMPLATE`); `ENQUIRY_EMAIL_SUBJECT` | All PHP code |

**Growing to millions of products or enquiries** (detail and diagrams in `docs/PLAN.md` §8):
- **Products:** sync them from the supplier into our own read model (RDS) with queued, idempotent imports. A `DatabaseProductSource` answers `ProductQuery` with indexed SQL, and keyset pagination replaces offset. Full-text search moves to OpenSearch.
- **Caching:** `CachedProductSource` already decorates any source. Point `CACHE_STORE` at Redis and put CloudFront in front of the `GET` endpoints.
- **Enquiries:** they're already persist-then-queue, so the next step is SQS with autoscaled workers, an idempotency key on `POST`, and monthly archiving.
- **Scaling the API:** it's stateless (no sessions), so it scales horizontally behind an ALB.

## With another day I would

1. Add an admin screen and a `DatabaseFeaturedProductRepository` so the owner can change top-rated lists without a deploy.
2. Add a `DatabaseProductSource` with a queued import from Fake Store, plus MySQL FULLTEXT search, to prove the seam with a second implementation.
3. Add a Playwright E2E test of the enquiry journey, plus PHPStan level 8.
4. Add observability: request IDs, structured JSON logs, metrics for upstream latency and errors, and alerts on failed jobs.
5. Add an idempotency key on enquiry submission. The button is disabled while sending, but a client retry on a flaky network could still create a duplicate.
6. Write Terraform or CDK for the AWS target in `docs/PLAN.md`.
