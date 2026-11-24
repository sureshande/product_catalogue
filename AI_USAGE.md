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
4. **"Use our partner portal UI for the React design."** Reused the *design language* (Bootstrap 5, Public Sans,
   azure/jade palette, gradient accent, top bar plus sidebar shell, offcanvas forms, dark mode), but the AI
   flagged that the employer's logo, proprietary icon font and CSS files must not be copied into a repo
   going to another company. Rebuilt with Bootstrap Icons and a token file.
5. **Testing rule: "a test that cannot fail is worse than none."** After a false-positive test was found
   (below), mutation checks were run on key tests: break the code, watch the test fail, restore.

## Where the AI got it wrong, and how it was caught

| What went wrong | How it was caught | Fix |
|---|---|---|
| Search used substring matching, so **"mens" matched "Womens"** (a women's T-shirt came back for "mens cotton"). | Smoke-testing the real API by hand before writing the feature tests. | Word-start matching, apostrophes ignored; a dataset test covers the edge cases. |
| **The "serve stale cache when the supplier is down" test passed without testing anything.** `Http::fake()` appends stubs and the first match wins, so "supplier down" never took effect after "supplier up". | A sibling test (503 when the supplier is down) failed unexpectedly, which exposed the helper bug. | `fakeStoreDown()` swaps in a fresh HTTP factory. Verified by deleting the stale fallback and watching the test fail. |
| The enquiry panel's reset effect depended on the `onClose` prop, so a parent passing a new function on each render would **wipe the form while the user typed**. | Code review of the generated component. | The callback is held in a ref; the effect depends only on `open`. |
| Behind nginx, every customer would have shared nginx's IP, so **one user could exhaust the enquiry rate limit for everyone**. | Reviewing the Docker topology against the rate limiter. | `TRUSTED_PROXIES` env; the API port isn't published, so only nginx can set `X-Forwarded-For`. |
| `docker compose` would have tried to **pull** the worker's image (a locally built tag) before building it. | Review of the compose file. | The worker also declares `build:`, which shares the cached image. |

## What I reviewed myself

<!-- Fill in honestly: which files you read line by line, what you changed by hand, anything you'd still question. -->

## Time spent

<!-- Fill in your real numbers. Going over the time-box is fine if stated honestly. -->

| Activity | Time |
|---|---|
| Planning (brief, PLAN.md, decisions) | |
| Building (backend, frontend, Docker) | |
| Testing | |
| Reviewing AI output and docs | |
| **Total** | |
