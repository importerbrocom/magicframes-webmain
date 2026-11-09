# Magic Frames Studio

Laravel 13 + Filament 5 backend for the Magic Frames Studio website. The public design
(`public/css/style.css`: cream / ink / rose palette, Cormorant Garamond + Jost + Great Vibes)
is unchanged; pages are being moved from static HTML (`legacy-html/`) to Blade views that
read their content from the database.

## Status

| Stage | Scope | State |
|---|---|---|
| 1 | Laravel + Filament foundation, restored static assets | done |
| 2 | Gallery & video database, public JSON API, admin management, roles | **done** |
| 3 | Header/navigation, pages & SEO, services, testimonials, homepage, site settings | **done** |
| 4 | Enquiries inbox + contact form API | **done** |
| 5a | Blade framework, home, about/services/contact/custom pages, 404, SEO, sitemap | **done** |
| 5b | Gallery, portfolio, video gallery, lightbox, blog | **done** |
| 6 | Production hardening, backups, deployment guide, requirements report | **done** |

## Documentation

* **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** — server setup, nginx/Apache/cPanel, cron, e-mail, backups & restore, updates, launch checklist
* **[docs/ADMIN-GUIDE.md](docs/ADMIN-GUIDE.md)** — how the studio team manages the website
* **[docs/REQUIREMENTS-STATUS.md](docs/REQUIREMENTS-STATUS.md)** — what is done, what differs from the brief, what is still needed
* `.env.production.example`, `scripts/deploy.sh`, `scripts/restore.sh`, `docs/nginx.conf.example`

## Requirements

PHP 8.3+ (extensions: gd with WebP, exif, fileinfo, intl, mbstring, pdo_mysql or pdo_sqlite, zip),
Composer 2, MySQL 8 / MariaDB 10.6 (SQLite works for development).

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate
# set DB_* in .env (see below) and ADMIN_EMAIL / ADMIN_PASSWORD
php artisan migrate --force
php artisan db:seed --force      # roles, first super-admin, categories, pages, menus (safe to re-run)
php artisan storage:link         # exposes storage/app/public as /storage
php artisan serve                # admin: http://localhost:8000/admin
```

MySQL:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magicframes
DB_USERNAME=...
DB_PASSWORD=...
```

The seeder only creates category *names* (Wedding Photography, Pre-Wedding Photography, …).
It never inserts images, videos, contact details or testimonials.

## Admin panel (`/admin`)

| Role | Access |
|---|---|
| `super-admin` | everything, including users |
| `editor` | all content, not users/settings |
| `enquiry-handler` | enquiries only (stage 4) |

Inactive users and users without a role cannot enter the panel.

* **Gallery → Categories**: unlimited categories, cover image, SEO fields, drag to reorder, publish toggle, “Add images” shortcut.
* **Gallery → Images**: select many files at once (JPG/PNG/WebP, 20 MB each, preview before saving). Edit title / alt / description, replace the file, feature, publish, reorder (drag), bulk publish / unpublish / feature / move / delete. WebP versions at 480 / 960 / 1600 px are generated automatically (never upscaled); deleting or replacing an image removes all its files.
* **Videos**: upload MP4 / WebM / MOV (default 500 MB) or paste a YouTube / Vimeo link (type and id detected automatically; YouTube thumbnail fetched automatically). Optional custom thumbnail, categories, featured / published.

## Public API (`/api/v1`, JSON, rate-limited, cached until content changes)

| Endpoint | Query |
|---|---|
| `GET gallery/categories` | |
| `GET gallery/categories/{slug}` | `page`, `per_page` |
| `GET gallery/images` | `category`, `featured`, `search`, `page`, `per_page` (max 60) |
| `GET video-categories` | |
| `GET videos` | `category`, `featured`, `search`, `page`, `per_page` |
| `GET videos/{slug}` | |

Only published content is returned. Any admin change bumps a cache version, so the API
reflects edits immediately.

## Website content (stage 3)

Admin sidebar groups:

* **Website** → *Pages* (title, URL, banner, rich content, repeatable content blocks: text / image+text / image / video / call-to-action, publish toggle, SEO tab with title, description, social image, canonical, noindex), *Services*, *Testimonials*, *Site settings* (studio name, footer text, phone, WhatsApp, email, address, hours, Google Maps embed, social links, default SEO).
* **Header & Menus** → *Header settings* (logo, show/hide, sticky, background style, call-to-action button, floating enquiry button, social icons in mobile menu) and *Menu items* (left / right / mobile-only / footer; link to a page or a custom URL; label, order, visibility, new tab).
* **Homepage** → *Home sections* (edit text/images, reorder by drag, show/hide each block), *Hero slides*, *Statistics*.

Built-in pages (home, about, services, portfolio, gallery, videos, blog, contact, shop, privacy, terms) can be edited and unpublished but not deleted. The Privacy and Terms pages start **unpublished** until the studio supplies its own legal text.

**Nothing is invented.** The seeder creates structure only. Phone, email, address, hours, social links, services, testimonials, statistics and legal text start empty and list blocks with no content are not rendered. Fill them in under *Site settings*, *Services*, *Testimonials* and *Statistics*.

Additional API endpoints: `GET site` (branding, header, menus, footer, contact, social), `home` (hero + ordered visible blocks with their data), `pages`, `pages/{slug}` (with blocks and resolved SEO), `services`, `services/{slug}`, `testimonials`.

## Enquiries (stage 4)

`POST /api/v1/enquiries` (JSON) accepts `name`, `email`, `message` (required) and `phone`, `event_type`, `event_date`, `source_page` (optional). `GET /api/v1/enquiry-options` returns the event-type list (edit it in `config/enquiry.php`). Responses: `201` stored, `422` validation errors (per field), `429` after 5 requests/minute from one IP.

Spam protection: hidden honeypot field `website` (must stay empty — the form in stage 5 renders it off-screen; filled submissions get a fake success and are discarded), per-IP rate limit, HTML stripped from text, strict validation (past event dates rejected).

Email alert: sent to the address in *Site settings → Email* (or `ENQUIRY_NOTIFY_EMAIL`). Configure real SMTP via the `MAIL_*` variables in `.env` (the default `log` mailer only writes to `storage/logs`). A mail failure is logged and never loses the enquiry.

Admin → **Enquiries**: inbox with unread badge, search, filters (status, event type, date range), inline status dropdown (New / Contacted / In Progress / Completed), detail view, internal notes, bulk status change, delete, and **Export CSV** of the current filtered view (formula-injection safe). The dashboard shows counts per status. Customer-submitted fields are read-only in the admin.

Privacy: enquiries contain personal data (name, email, phone, IP). Delete old enquiries when no longer needed and mention this in your published Privacy Policy.

## Public website (stage 5a)

Pages are server-rendered Blade views in the **original design** (`public/css/style.css` untouched; `public/css/enhancements.css` only adds on top and uses the original colour/font variables). Content comes from the same cached data layer as the API (`App\Services\SiteData`), so the website and `/api/v1` can never disagree.

* `/` Home — blocks in the order set under *Admin → Homepage → Home sections*; empty blocks are not rendered.
* `/{slug}` — every admin-managed page (about, services, contact, privacy-policy, terms-and-conditions, any page you create). Unpublished pages return the custom 404.
* Header, menus, footer, contact details and social links come from *Header settings*, *Menu items* and *Site settings*. Fields left empty simply do not appear.
* `/contact` and the home page share one **real** enquiry form (`public/js/site.js` → `POST /api/v1/enquiries`) with inline field errors, a success message and spam protection.
* `/sitemap.xml` (published, indexable pages + galleries with photos) and `/robots.txt` are generated dynamically; SEO tags (title, description, canonical, Open Graph, Twitter, noindex) come from each page's SEO tab with site-wide defaults.
* Images: gallery photos use responsive `srcset` WebP variants; hero/banner/card images are served as resized, cached WebP (`storage/app/public/_cache/`), original files stay untouched. Very large camera files are handled safely (`MEDIA_MAX_PROCESSING_MEMORY_MB`, default 1024).
* Motion: page fade-in/out, scroll reveals, animated word-by-word headings, parallax, counters, hero slider — all disabled automatically for visitors who prefer reduced motion.

Removed from the original template because they were fake: the countdown timer (counted down to "90 days from page load"), the form that only pretended to send, the invented team members/values/stats/testimonials, and the Florence address/phone. The stock photos that were baked into the CSS no longer show; upload your own under *Home sections*.

## Galleries, videos & blog (stage 5b)

| URL | What it shows |
|---|---|
| `/portfolio` | one card per gallery category that has published photos |
| `/gallery` , `/gallery?category=slug` | all photos, category filter (in place, no reload), **masonry ⇄ grid** switch (remembered), *Load more*, lazy-loaded responsive WebP images |
| `/gallery/{category}` | individual gallery with description, SEO fields, previous/next gallery |
| `/videos`, `?category=`, `?play=slug` | film thumbnails, category filter, full-screen-capable player (YouTube/Vimeo embeds or uploaded MP4/WebM). Nothing is downloaded until a visitor presses a film — page weight stays small. `?play=slug` links open a film directly |
| `/blog`, `/blog/{slug}` | articles with category filter, search, pagination, reading time, article structured data |

**Lightbox:** click any photo → full-screen viewer with previous/next buttons, ← → and Esc keys, swipe on touch screens, captions (title + description from the admin), image counter, browser full-screen button, focus kept inside the dialog, page scroll locked while open.

**Blog admin** (*Website → Blog posts*): rich-text editor with image attachments, cover image + alt text, category label, author, excerpt, SEO tab, draft / published, and **scheduled publishing** (set a future date; the post appears when the date arrives — within ~15 minutes because public pages are cached). API: `GET /api/v1/blog`, `/blog/{slug}`, `/blog-categories`.

Footer links to *Gallery*, *Privacy Policy* and *Terms & Conditions* are created by the seeder; the legal links stay hidden automatically until you publish those pages.

The original template's **Shop** page contained invented products, so it is now a normal editable page (no product catalogue). Tell us if you want a real shop later.

## Upload limits (important for production)

Set in `php.ini` (and nginx `client_max_body_size`) at least:
```
upload_max_filesize = 512M
post_max_size = 520M
max_execution_time = 300
```
and keep `MEDIA_MAX_VIDEO_KB` in `.env` at or below it. For very large films prefer a YouTube/Vimeo link.

## Backups (automatic)

`php artisan site:backup` (scheduled daily 02:30 via cron, keeps 14) writes a zip with the database and all uploads to `storage/app/backups`; restore with `scripts/restore.sh`. Details in docs/DEPLOYMENT.md. Manual commands:

* Database: `mysqldump --single-transaction -u USER -p magicframes | gzip > backup-$(date +%F).sql.gz` (daily cron, keep 14+ days, copy off-server).
* Media: back up `storage/app/public/` (e.g. `rsync -a` or `tar czf`).
* Restore: import the SQL dump, restore `storage/app/public/`, run `php artisan storage:link`.

## Tests

```bash
php artisan test
```
Covers admin login/logout and credentials, role permissions, multi-image upload, WebP variant
generation, file-type rejection, image replace / delete (files removed), video upload and
YouTube/Vimeo links, publish/unpublish, API filtering, and cache invalidation.
