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

## Setup

The only thing you need is **Docker** (with Docker Compose v2). PHP, Node and MySQL all run inside containers.

### 1. Install Docker

Skip this step if `docker compose version` already prints a version.

<details open>
<summary><b>Ubuntu / Debian, including WSL2 on Windows</b></summary>

These are the commands from [Docker's official guide](https://docs.docker.com/engine/install/ubuntu/).
On WSL2, first check that systemd is enabled: `/etc/wsl.conf` should contain `[boot]` / `systemd=true`.
If it doesn't, add those lines, run `wsl --shutdown` in Windows PowerShell, and reopen the terminal.

```bash
# Remove old/unofficial packages if present (safe if none are installed)
for pkg in docker.io docker-doc docker-compose docker-compose-v2 podman-docker containerd runc; do
  sudo apt-get remove -y $pkg 2>/dev/null
done

# Add Docker's apt repository
sudo apt-get update
sudo apt-get install -y ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] \
https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine + Compose plugin
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Start Docker now and on every boot
sudo systemctl enable --now docker

# Run docker without sudo (then open a NEW terminal, or run: newgrp docker)
sudo usermod -aG docker $USER
```

For Debian, replace `ubuntu` with `debian` in both URLs.
</details>

<details>
<summary><b>macOS</b></summary>

Install [Docker Desktop for Mac](https://docs.docker.com/desktop/setup/install/mac-install/) (choose Apple
silicon or Intel), open it once, and wait until the whale icon says *Docker Desktop is running*.
Or with Homebrew: `brew install --cask docker`, then open Docker from Applications.
</details>

<details>
<summary><b>Windows (without WSL)</b></summary>

Install [Docker Desktop for Windows](https://docs.docker.com/desktop/setup/install/windows-install/),
keep the *Use WSL 2* option ticked, restart if asked, and open Docker Desktop once. Run the commands
below in PowerShell or a WSL terminal.
</details>

### 2. Check Docker works

```bash
docker --version             # Docker version 2x.x
docker compose version       # Docker Compose version v2.x
docker run --rm hello-world  # prints "Hello from Docker!"
```

### 3. Get the code

```bash
git clone <this repo URL> product-catalogue
cd product-catalogue
```

### 4. Start everything

```bash
docker compose up --build
```

The first run takes about 3–5 minutes to download images and build. Later runs take seconds. It's ready
when the log shows `web-1` started and `api-1` answering `/api/health`. No `.env` file is needed: every
setting has a working default. To change one, run `cp .env.example .env` and edit it.

It starts five containers:

| Container | What it is |
|---|---|
| `web` | nginx serving the React app, proxying `/api` to the backend |
| `api` | Laravel API (runs database migrations on start) |
| `worker` | Laravel queue worker that sends enquiry emails |
| `mysql` | MySQL 8.4: enquiries, queue, cache |
| `mailpit` | A fake mail server with a web inbox, so no email account is needed |

### 5. Open it

| What | URL |
|---|---|
| **The app** | http://localhost:8080 |
| **Sales inbox** (Mailpit: enquiry emails arrive here) | http://localhost:8025 |
| API health check | http://localhost:8080/api/health → `{"status":"ok"}` |

Try the acceptance criteria:
1. **Home**: the 5 "top rated" products chosen in config.
2. **Category**: click *Electronics* in the sidebar for its top rated products plus more products in that category.
3. **Search**: type `jacket`, click **Search**.
4. **Product**: click any tile.
5. **Enquiry**: on a product, click **Enquire**, fill in the form, and you get a *Thank you*. Open
   http://localhost:8025 to see the email in the sales inbox.

### 6. Stop, restart, reset

```bash
Ctrl+C                       # stop (when running in the foreground)
docker compose up -d         # start in the background
docker compose logs -f api   # follow logs of one service (api, worker, web, mysql, mailpit)
docker compose down          # stop and remove containers (data is kept)
docker compose down -v       # also delete the database volume (fresh start)
```

### Troubleshooting

| Symptom | Fix |
|---|---|
| `permission denied ... /var/run/docker.sock` | You aren't in the `docker` group yet. Run `newgrp docker` or open a new terminal (after `sudo usermod -aG docker $USER`). |
| `Cannot connect to the Docker daemon` | Start it: `sudo systemctl start docker` (Linux/WSL), or open Docker Desktop (Mac/Windows). |
| `port is already allocated` on 8080 or 8025 | Another program uses the port. Stop it, or change the left-hand port in `docker-compose.yml` (e.g. `"8081:80"`). |
| Home page shows *"catalogue is temporarily unavailable"* | The containers can't reach `https://fakestoreapi.com`. Check your internet/proxy; `docker compose logs api` shows the error. Data is cached for 10 minutes once loaded. |
| No email in Mailpit | Check the worker: `docker compose logs worker`. Emails are sent a second or two after the *Thank you*. |
| Anything else odd after pulling changes | `docker compose down -v && docker compose up --build` |

## Run the tests

Tests need no network. The backend suite uses SQLite in memory by default.

```bash
# Backend (PHP 8.3+ with the pdo_sqlite extension)
cd backend && composer install && ./vendor/bin/pest

# ...or, if your PHP has no SQLite driver, against an empty MySQL database:
DB_CONNECTION=mysql DB_DATABASE=catalogue_test DB_USERNAME=<user> DB_PASSWORD=<password> ./vendor/bin/pest

# ...or with no local PHP at all, inside Docker:
docker run --rm -v "$PWD/backend":/app -w /app composer:2.8 sh -c "composer install -q && ./vendor/bin/pest"

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

## Configuring the enquiry email

| What | Where |
|---|---|
| **Content** (all wording, layout, styling) | One Blade template: [`backend/resources/views/mail/enquiry.blade.php`](backend/resources/views/mail/enquiry.blade.php). Edit it, or point `ENQUIRY_EMAIL_TEMPLATE` at another view. |
| **Subject** | `ENQUIRY_EMAIL_SUBJECT` (default `New enquiry: :product`). Placeholders: `:product` `:product_id` `:price` `:name` `:email` `:enquiry_id` |
| **Recipient** | `SALES_INBOX_EMAIL` |

The template receives `$enquiry` (all fields), `$price` and `$brand`. Print customer input with `{{ }}` so
it's escaped. With Docker, set these in the root `.env` and run `docker compose up -d`.

## Look & feel

A neutral placeholder theme until the real brand arrives. It's built only from MIT-licensed open source
(Bootstrap 5, Bootstrap Icons) and the system font stack, so there is nothing to license and no third-party
branding. Re-brand by editing [`frontend/src/theme/tokens.scss`](frontend/src/theme/tokens.scss).

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
