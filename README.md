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
| 4 | Enquiries + working contact form | todo |
| 5 | Blade pages, dynamic gallery/video frontend, animations, SEO | todo |

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

## Upload limits (important for production)

Set in `php.ini` (and nginx `client_max_body_size`) at least:
```
upload_max_filesize = 512M
post_max_size = 520M
max_execution_time = 300
```
and keep `MEDIA_MAX_VIDEO_KB` in `.env` at or below it. For very large films prefer a YouTube/Vimeo link.

## Backups

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
