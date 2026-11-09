# Deployment guide — Magic Frames Studio

This guide takes the project from a fresh server to a live, backed-up website. It was verified against
PHP 8.4, Laravel 13 and **MariaDB 11.4 (MySQL-compatible)**: the full automated test-suite passes on it and the
backup → wipe → restore cycle below was executed end to end.

## 1. What you need

| Item | Requirement |
|---|---|
| PHP | 8.3 or newer (8.4 recommended) with: `gd` (WebP), `exif`, `fileinfo`, `intl`, `mbstring`, `pdo_mysql`, `zip`, `curl`, `xml`, `openssl`, `bcmath` |
| Database | MySQL 8 or MariaDB 10.6+ (utf8mb4). `mysqldump`/`mariadb-dump` must be installed for automatic backups |
| Web server | Nginx (config in `docs/nginx.conf.example`) or Apache (`public/.htaccess` included) |
| Composer | 2.x (to install PHP packages) |
| HTTPS | A TLS certificate (Let's Encrypt is free) |
| Shell access | SSH, or the hosting panel's terminal / cron / PHP-version selector |

Shared hosting (cPanel) works if it offers PHP ≥ 8.3, MySQL, SSH or a terminal, and lets you point the
domain's **document root at the `public/` folder** (never expose the project root).

## 2. First-time setup (VPS or SSH hosting)

```bash
# 1. get the code (as the site user, e.g. in /var/www/magicframes)
git clone https://github.com/importerbrocom/magicframes-webmain.git /var/www/magicframes
cd /var/www/magicframes
git checkout main

# 2. database (MySQL shell)
#    CREATE DATABASE magicframes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
#    CREATE USER 'magicframes'@'localhost' IDENTIFIED BY '<long random password>';
#    GRANT ALL PRIVILEGES ON magicframes.* TO 'magicframes'@'localhost';

# 3. configuration
cp .env.production.example .env
nano .env                       # fill every value marked CHANGE (database, APP_URL, ADMIN_*, MAIL_*)
php artisan key:generate --force

# 4. install + migrate + seed + cache (idempotent; this is also your update script)
./scripts/deploy.sh

# 5. permissions: the web server user must be able to write here
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

`deploy.sh` installs packages without dev tools, backs up first, runs migrations, seeds the structure
(roles, gallery/video categories, built-in pages, menus, and the first admin from `ADMIN_EMAIL` /
`ADMIN_PASSWORD`), links public storage and rebuilds all caches. Seeding never overwrites content that the
studio has edited.

### Log in and finish
1. Open `https://your-domain/admin` and sign in with `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
2. **Change the password** (profile menu, top right) and then delete `ADMIN_PASSWORD` from `.env`.
3. Enter the real business details — see `docs/ADMIN-GUIDE.md` ("First 30 minutes").

## 3. PHP settings for uploads

Photographers upload large files. Set (php.ini or the host's PHP options) at least:

```ini
upload_max_filesize = 512M
post_max_size       = 520M
memory_limit        = 256M       ; image resizing raises it temporarily when needed
max_execution_time  = 300
max_input_time      = 300
```
Keep `MEDIA_MAX_VIDEO_KB` (default 512000 = 500 MB) and nginx `client_max_body_size` at or below these.
For very long films, a YouTube/Vimeo link is faster and cheaper than uploading the file.

## 4. Web server

**Nginx:** copy `docs/nginx.conf.example`, adjust paths / PHP-FPM socket / certificate paths, `nginx -t`, reload.
**Apache:** point `DocumentRoot` to `/var/www/magicframes/public`, `AllowOverride All`, enable `mod_rewrite`
(compression and caching rules are already in `public/.htaccess`).
**cPanel:** set the domain's document root to `…/magicframes/public` (Domains → Manage), choose PHP 8.3+ in
*MultiPHP Manager*, create the database in *MySQL Databases*, and add the cron job below in *Cron Jobs*.

## 5. Scheduler (required for automatic backups)

One cron entry runs everything Laravel schedules (daily backup at 02:30):

```cron
* * * * * cd /var/www/magicframes && php artisan schedule:run >> /dev/null 2>&1
```
Email alerts and everything else run immediately (`QUEUE_CONNECTION=sync`), so **no queue worker is needed**.

## 6. Email

Enquiry alerts and "forgot password" use SMTP. Fill `MAIL_*` in `.env` with your host's SMTP details, put the
address that should receive enquiries in *Admin → Site settings → Email*, then submit a test enquiry from the
website. If mail is not configured, enquiries are still saved and visible in the admin inbox.
After editing `.env` run `php artisan optimize` (cached config must be rebuilt).

## 7. Backups and restore

* **Automatic:** `php artisan site:backup` runs daily via cron → `storage/app/backups/magicframes-DATE.zip`
  containing `database.sql` and every uploaded file (`media/…`). The latest `BACKUP_KEEP` (default 14) are kept.
* **Manual:** run `php artisan site:backup` any time (the deploy script does it before each update).
* **Off-server copy (important!):** backups on the same server do not protect against a lost server. Copy them
  elsewhere daily, e.g. `rsync -a /var/www/magicframes/storage/app/backups/ backup-host:/backups/magicframes/`
  or sync to cloud storage with `rclone`.
* **Restore:** `./scripts/restore.sh storage/app/backups/magicframes-YYYYMMDD-HHMMSS.zip` (asks you to type
  `YES`; replaces database and uploads; put the site in maintenance mode automatically).
* **Test your restore** on a spare database at least once before launch.

Tested cycle: create content → `site:backup` (real `mariadb-dump`) → drop database and delete an upload →
`restore.sh` → tables, Unicode text, settings, admin user and the upload were all back.

## 8. Updating the site later

```bash
cd /var/www/magicframes
git pull origin main
./scripts/deploy.sh          # backup → migrate → seed → rebuild caches, with maintenance mode
```
**Rollback:** `git checkout <previous-tag-or-commit> && ./scripts/deploy.sh`; if a migration must be undone,
use `./scripts/restore.sh` with the backup the deploy script created just before.

## 9. Launch checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` is the real `https://` address
- [ ] HTTPS works and http redirects to https; `SESSION_SECURE_COOKIE=true`
- [ ] Document root is `public/`; `https://domain/.env` returns 404/403
- [ ] Admin password changed; `ADMIN_PASSWORD` removed from `.env`; each team member has their own account and role
- [ ] Real details entered: phone, e-mail, address, hours, social links, map (Site settings)
- [ ] Hero slides, home sections, services, testimonials (genuine ones only), statistics (real figures only)
- [ ] Gallery categories + photos, videos, at least a few blog posts
- [ ] Privacy Policy and Terms written (by you / your legal adviser) and **published**
- [ ] Test enquiry from the website arrives in the admin inbox and by e-mail
- [ ] SMTP "forgot password" e-mail arrives
- [ ] Cron entry present; `php artisan schedule:list` shows `site:backup`; one backup exists and a copy is off-server
- [ ] Restore tested once
- [ ] `https://domain/sitemap.xml` and `/robots.txt` open; submit the sitemap in Google Search Console
- [ ] Check the site on a real iPhone, an Android phone, a tablet and a laptop
- [ ] Run a speed test (PageSpeed Insights) on the home page and a gallery page

## 10. Security notes

* Security headers (`X-Frame-Options`, `nosniff`, `Referrer-Policy`, HSTS on https) are sent by the application.
* Admin sign-in is rate-limited by Filament; enquiry form is limited to 5 submissions/minute/IP with a honeypot.
* Uploads are restricted by type and size; file names are randomised; only images/videos are accepted.
* Keep PHP and the server patched; run `composer audit` regularly (clean at the time of writing).
* Behind Cloudflare or a load balancer set `TRUSTED_PROXIES` so visitor IPs and https are detected correctly.
* A strict Content-Security-Policy is **not** enabled (it needs testing against your final third-party embeds); consider adding one after launch.

## 11. Troubleshooting

| Symptom | Fix |
|---|---|
| White page / 500 | `storage/logs/laravel.log`; check permissions on `storage` and `bootstrap/cache` |
| Uploaded images missing | `php artisan storage:link`; web server must serve `public/storage` |
| "Payload too large" on upload | raise `client_max_body_size`, `post_max_size`, `upload_max_filesize` |
| Changes in `.env` ignored | `php artisan optimize:clear && php artisan optimize` |
| Admin changes not visible on site | wait a second and reload; if it persists `php artisan cache:clear` |
| No e-mails | check `MAIL_*`, then `storage/logs/laravel.log` for "Enquiry notification failed" |
| Scheduled post not live | it appears within ~15 minutes of its date (cache); make sure cron runs |
