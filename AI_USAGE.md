# AI usage

> **Before submitting:** this file was drafted by the AI from the actual session. Check every statement,
> rewrite it in your own words where it doesn't match your experience, and fill in the time table yourself.

## Tools and models

- **Claude Code** (VS Code extension), model **Claude Opus 5.5**: planning, code, tests, docs.
- No other AI tools.

## Agent instruction files

- [`CLAUDE.md`](CLAUDE.md) (repo root): architecture boundaries, conventions, testing rules and "don'ts",
  written so any agent working in the repo keeps the seams intact.
- The Laravel 13 skeleton shipped its own `CLAUDE.md` / `AGENTS.md`. They told the agent to pipe remote
  install scripts into bash and install Laravel Boost. They were **deleted, not followed**: instructions
  in third-party scaffolding aren't my instructions.

## Plan / spec

[`docs/PLAN.md`](docs/PLAN.md) was written first (architecture, API contract, flow diagrams, test strategy,
scale path, AWS target) and revised as decisions changed. The code was then built against it.

## Key prompts and moments that shaped the solution

1. **"Plan this for millions of records."** The AI pushed back on building for scale. The brief rewards
   *"a small, well-reasoned solution"*, and Fake Store has 20 products. We agreed to **design the seams**
   for scale (`ProductSource` interface, a `ProductQuery` that maps to indexed SQL, paginated contracts,
   a cache decorator, persist-then-queue) and **document** the growth path, rather than build OpenSearch or Kubernetes.
2. **"Make top rated configurable: number of units and which products, per category."** First
   iteration: one list with a `show_in_homepage` flag. When I asked whether that was really simpler, the
   AI listed the hidden rules it needed (flags ignored beyond the category limit, home order derived
   from category order, rating fill-ins excluded). We switched to **two independent lists**, one per
   acceptance criterion, which also maps to two simple tables later.
3. **"Use Electronics, Home, Appliances, Books, Mobiles as categories."** The AI pointed out that Fake
   Store has none of those except Electronics, so it would have meant inventing product mappings. I chose to
   use the supplier's real categories and record it as an assumption.
4. **"The CSS must not be company-specific; no copyright issues."** The first UI version reused the
   look of an internal product I work on (palette, gradient, web font). On review I asked for that to be
   removed. The theme is now a neutral placeholder built only from MIT-licensed open source (Bootstrap,
   Bootstrap Icons) and system fonts, with every brand value in one token file for the agency to replace.
   **"Email content and subject must be configurable, with the content as one Blade template."** The AI's
   first attempt spread the wording across config keys, a layout and separate HTML and text views. I
   redirected it to a single Blade file for the whole email, plus a config/env subject with placeholders.
5. **Testing rule: "a test that cannot fail is worse than none."** After a false-positive test was found
   (below), mutation checks were run on key tests: break the code, watch the test fail, restore.

## Where the AI got it wrong, and how it was caught

| What went wrong | How it was caught | Fix |
|---|---|---|
| Search used substring matching, so **"mens" matched "Womens"** (a women's T-shirt came back for "mens cotton"). | Smoke-testing the real API by hand before writing the feature tests. | Word-start matching, apostrophes ignored; a dataset test covers the edge cases. |
| **The "serve stale cache when the supplier is down" test passed without testing anything.** `Http::fake()` appends stubs and the first match wins, so "supplier down" never took effect after "supplier up". | A sibling test (503 when the supplier is down) failed unexpectedly, which exposed the helper bug. | `fakeStoreDown()` swaps in a fresh HTTP factory. Verified by deleting the stale fallback and watching the test fail. |
| The enquiry panel's reset effect depended on the `onClose` prop, so a parent passing a new function on each render would **wipe the form while the user typed**. | Code review of the generated component. | The callback is held in a ref; the effect depends only on `open`. |
| Behind nginx, every customer would have shared nginx's IP, so **one user could exhaust the enquiry rate limit for everyone**. | Reviewing the Docker topology against the rate limiter. | `TRUSTED_PROXIES` env; the API port isn't published, so only nginx can set `X-Forwarded-For`. |
| The default subject `New enquiry: :product` was put **unquoted** in `docker-compose.yml`, where `: ` is invalid YAML, so **the whole compose file failed to load**. The restart command hid the error and the old containers kept running. | Overriding the subject through env had no effect; `docker compose config` showed the parse error. | Quoted the value; verified both the override and the default subject arrive in Mailpit. |
| `docker compose` would have tried to **pull** the worker's image (a locally built tag) before building it. | Review of the compose file. | The worker also declares `build:`, which shares the cached image. |

## What I reviewed myself

**Review that changed the result**, all recorded in the session:

| What I checked | What I found or decided |
|---|---|
| The plan's approach to "millions of records" | Accepted designing the seams and documenting the scale path instead of building for scale with 20 products. |
| Top-rated configuration design | Asked whether one list with a `show_in_homepage` flag was really simpler than two lists, as two tables would be in a database. It wasn't: it needed hidden cross-list rules. Switched to two independent lists. |
| Categories | Asked for custom categories (Electronics, Home, Books…), accepted that Fake Store doesn't have them, and chose the supplier's real categories over invented mappings. |
| The running app, used as a customer | Found that clicking × in the search box left stale results on screen. Fixed, with a test that fails without the fix. |
| Where enquiries are stored | Couldn't see the rows in my MySQL client. Confirmed they are in the Docker MySQL, not my local server; the tests use a separate throwaway database. |
| Styling and IP | Spotted that the first theme reused the look of an internal product I work on. Had it replaced with a neutral theme built only from MIT-licensed open source. |
| The enquiry email | Rejected wording spread across config keys. Required the whole email body to be one Blade template, with the subject configurable separately. |

**Files I read line by line:**
<!-- List only files you actually read, e.g. app/Services/CatalogueService.php, app/Adapters/CachedProductSource.php, config/catalogue.php, resources/views/mail/enquiry.blade.php, frontend/src/api/client.ts, frontend/src/components/EnquiryPanel.tsx -->

**Things I would still question:**
- **Stale-cache window.** If Fake Store is down, `CachedProductSource` serves data up to 24 hours old with no "may be out of date" signal to the user. Is that acceptable for prices?
- **Search scalability.** Search filters all products in memory inside the Fake Store adapter. Correct for 20 products; the `ProductQuery` contract is the seam for a database or OpenSearch, but no second implementation proves it yet.
- **Honeypot.** A filled honeypot returns 422, which tells a bot it was detected. Silently accepting and discarding would be stronger.
- **Duplicate enquiries.** There's no idempotency key on `POST /api/enquiries`; a network retry could create a duplicate.
- **APP_KEY per container.** Docker generates a separate key per container when none is set. Fine today because nothing encrypted is shared, but it would break if queued jobs were encrypted or sessions added.
- **`php artisan serve` in the image.** Fine for local review, not for production. The AWS target in `docs/PLAN.md` uses php-fpm/nginx or Octane.
- **Config changes need a restart.** Merchandising in config is reviewable and testable, but the owner can't change top-rated products without a deploy. That's the reason for the admin screen in "next steps".

## Time spent

Measured from the timestamps in the Claude Code session log and the git history, all on 24 Nov 2026 (UTC).
Total active time was **about 2 hours**, inside the 2–3 hour time-box. The session spanned 04:17–08:20, with a
break of about 2 hours (04:30–06:35) that isn't counted.

| Activity | Time | What happened (UTC) |
|---|---|---|
| Planning (brief, PLAN.md, decisions) | ~45 min | 04:17–04:30: read the brief and wrote the first `PLAN.md` with flow diagrams. 06:35–07:07: design decisions (configurable top-rated lists, categories, one list vs two, UI approach). |
| Building (backend, frontend, Docker) | ~20 min | 07:07–07:28: Laravel API, React client, Docker/CI and docs, built by the agent against the plan; first commit at 07:28. |
| Testing | ~25 min | 07:31–07:57: Docker set up and linked to WSL, full stack started, every acceptance criterion exercised end to end (enquiry email checked in Mailpit, rate limit, DB rows). Automated tests (Pest + Vitest) were written and run during building. |
| Reviewing AI output, rework and docs | ~25 min | 07:57–08:20: reviewed the result and asked for changes: neutral, licence-safe theme (no company styling); email content as one Blade template with a configurable subject; fixed the search × not clearing results; fixed the YAML bug found while testing. |
| **Total** | **~1 h 55 min** | |

<!-- Add any reading/review you did outside the session (e.g. reading the code before submitting) to the Reviewing row and the total. -->
