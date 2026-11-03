# Production deployment checklist

Run through this list for every environment. The application is a Laravel 12
monolith: PHP-FPM, MySQL, a queue worker and one scheduler entry are enough.

## 1. Server prerequisites

- PHP 8.2 or newer with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`,
  `gd`, `bcmath` and `curl`.
- Composer 2 and Node.js 18+ on the build machine.
- MySQL 8 (or MariaDB 10.6+) with a dedicated database and user.
- HTTPS certificate, nginx (see `deploy/nginx-dph-system.conf`) and PHP-FPM.
- Optional: `ext-redis` if the cache, queue or session drivers are set to
  redis; `ext-brotli` if `RESPONSE_COMPRESSION` should use brotli.
- Raise PHP's `upload_max_filesize` and `post_max_size` (for example to `64M`)
  if administrators will upload backups to restore. Match nginx's
  `client_max_body_size`.

## 2. Build and upload

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Upload the project **without** `node_modules`, `.env`, `storage/logs/*` or
`tests`. Keep `public/build` from the build step.

Set the document root to `public/`. The rest of the project must stay outside
the web root.

## 3. Environment

1. Copy `deploy/env.production.example` to `.env`.
2. Replace every `CHANGE_ME`.
3. Generate the key once and keep a copy somewhere safe:

```bash
php artisan key:generate
```

4. Set `APP_URL` to the real HTTPS address, `SESSION_SECURE_COOKIE=true`,
   `SESSION_ENCRYPT=true`, `APP_DEBUG=false`, `SEED_DEMO_DATA=false` and
   `FORCE_HTTPS=true`.
5. Leave `ASSET_URL` unset unless a real CDN serves `/build`. A wrong value
   breaks every stylesheet and script.
6. Set a real `MAIL_MAILER` (password resets and reminders do not work on
   `log`), and an SMS driver if text messages are enabled.
7. If the cache/queue/session drivers are `redis`, confirm
   `php -m | grep redis` on **every** server, including queue workers.

## 4. First release

```bash
php artisan migrate --force
php artisan optimize        # config, routes, views and events
php artisan storage:link
php artisan admin:create    # if no administrator exists yet
```

Run `migrate --force` as part of the same release, before the new code takes
traffic. In particular, `2026_10_21_000002_encrypt_clinical_text_columns`
must run before the models read existing clinical text.

## 5. Background work

- Scheduler, every minute:

```cron
* * * * * cd /var/www/dph-system && php artisan schedule:run >> /dev/null 2>&1
```

- Queue worker: install `deploy/supervisor-dph-system.conf` and run
  `sudo supervisorctl reread && sudo supervisorctl update`.
- After every release: `php artisan queue:restart`.

## 6. Verify

- `GET /up` returns 200.
- Sign in as the system administrator; change the password.
- Open **System Health** and confirm:
  - database schema is up to date;
  - email and SMS are not on the `log` driver in production;
  - backup storage is durable if the server disk is not;
  - no failed jobs.
- Register a test facility, worker and passport.
- Scan the card, record a visit, print the card and certificate.
- Open the patient portal and check the access log.
- Run `php artisan backup:run`, download the copy, then delete it.
- In the browser console, confirm the CSP does not block Alpine or the camera.

## 7. Ongoing releases

```bash
php artisan down --retry=60
# upload the new release
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
```

## 8. Backups and retention

- Keep a copy of `APP_KEY` off the server. Backups cannot be read without it.
- Use `BACKUP_DISK=s3` (or an S3 compatible bucket) when the server disk is not
  durable.
- `backup:run` keeps the newest copies; the age limit is set on the Settings
  page.
- `audit:prune` runs daily and removes audit rows older than
  `AUDIT_LOG_RETENTION_DAYS` (default 365).

## 9. Do not ship

- `APP_DEBUG=true`
- `SEED_DEMO_DATA=true`
- an empty `APP_KEY`
- placeholder domains, buckets or mail credentials
- `MAIL_MAILER=log` or an enabled SMS feature on `SMS_DRIVER=log`
- `TRUSTED_PROXIES` containing public ranges
- `CORS_ENABLED=true` without an exact origin list
