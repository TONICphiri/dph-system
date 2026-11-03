# Deploying the Digital Health Passport on Vercel

This deployment uses the community [`vercel-php`](https://github.com/vercel-community/php)
runtime (`vercel-php@0.9.0`, PHP 8.5) and a small serverless bootstrap. It is a
real deployment option, but it is not the same as a normal PHP server. Read
[Platform limits](#platform-limits) before going live.

## Files involved

| File | Purpose |
| --- | --- |
| `vercel.json` | Builds `api/index.php`, routes every request to it, sets the region and daily crons. |
| `api/index.php` | Vercel entrypoint. Serves `public/` assets with cache headers, then boots Laravel. |
| `bootstrap/vercel.php` | No-op locally. On Vercel it moves writable storage to `/tmp`, trusts Vercel's proxy, and fills in safe HTTPS defaults. |
| `.vercelignore` | Keeps `.env`, `vendor`, `node_modules`, logs, backups, tests and local SQLite out of the upload. |

Vite assets in `public/build` are committed, so Vercel does **not** run `npm
build`. Composer dependencies are installed by the PHP runtime during its build
(the `vercel` Composer script then generates Laravel's package cache).

## 1. Set environment variables in Vercel

`.env` is intentionally excluded from the deployment. Add the values from your
local `.env` to **Vercel → Project → Settings → Environment Variables** for
Production (and Preview if you use preview deployments). Do not upload `.env`.

Use these Vercel-specific values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...                 # copy the existing key
APP_URL=https://your-domain.mw     # must be the real public URL
APP_TIMEZONE=Africa/Blantyre

LOG_CHANNEL=stderr                 # file logs disappear with the function

DB_CONNECTION=mysql
DB_HOST=gateway01.eu-central-1.prod.aws.tidbcloud.com
DB_PORT=4000
DB_DATABASE=dhp-System
DB_USERNAME=...
DB_PASSWORD=...
MYSQL_ATTR_SSL_CA=isrgrootx1.pem   # no quotes in the Vercel dashboard

SESSION_DRIVER=cookie              # stateless; no session table needed
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync              # no queue worker on Vercel

FILESYSTEM_DISK=s3
BACKUP_DISK=s3                     # local backups vanish with the function
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
# AWS_ENDPOINT=...                 # only for R2/other S3-compatible stores
# AWS_USE_PATH_STYLE_ENDPOINT=false

MAIL_MAILER=...                    # never leave this as "log" in production
MAIL_HOST=...
MAIL_PORT=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME="${APP_NAME}"

TRUSTED_PROXIES=REMOTE_ADDR        # bootstrap sets this if it is blank
FORCE_HTTPS=true
CRON_SECRET=...                    # long random value; Vercel sends it as a Bearer token
SEED_DEMO_DATA=false
```

`bootstrap/vercel.php` also supplies safe defaults if you forget them:

- `APP_STORAGE_PATH=/tmp/storage` and all writable subdirectories
- `LOG_CHANNEL=stderr`
- `TRUSTED_PROXIES=REMOTE_ADDR`
- `SESSION_SECURE_COOKIE=true`
- `FORCE_HTTPS=true`
- `APP_URL` from `VERCEL_PROJECT_PRODUCTION_URL`/`VERCEL_URL` when it is blank,
  `localhost`, or still contains `your-domain`
- `BACKUP_DISK=s3` when `AWS_BUCKET` is set

If you use a custom domain, set `APP_URL` explicitly anyway.

## 2. Prepare the database

Run migrations against TiDB from your machine or CI, not from a web request:

```bash
php artisan migrate --force
```

For a fresh database, run the required seeders with demo data disabled. Then
create the first administrator:

```bash
php artisan admin:create
```

TiDB Cloud must allow connections from Vercel. Vercel functions use dynamic
egress addresses, so either allow the public internet on the TiDB cluster's IP
access list or use TiDB Cloud's public endpoint with TLS. Keep
`MYSQL_ATTR_SSL_CA=isrgrootx1.pem` so the CA committed in the repository is
used.

## 3. Deploy

From the repository root:

```bash
npx vercel          # first deployment / preview
npx vercel --prod   # production deployment
```

Or connect the GitHub repository in the Vercel dashboard. The first build will
download Composer dependencies and should be slower than later builds. The PHP
function is deployed to `fra1` (Frankfurt), close to the TiDB `eu-central-1`
cluster.

Smoke test after deploying:

- `https://your-domain.mw/up` returns `200`
- `https://your-domain.mw/login` loads with CSS and JavaScript
- sign in and open **System Health**

## 4. Scheduled tasks

Vercel runs two cron jobs from `vercel.json` (times are UTC):

| UTC | Africa/Blantyre | Endpoint | Work |
| --- | --- | --- | --- |
| `0 5 * * *` | 07:00 | `/api/cron/reminders` | `reminders:send` |
| `0 0 * * *` | 02:00 | `/api/cron/maintenance` | `backup:run --scheduled`, `patients:separate-adults`, `audit:prune` |

Vercel automatically adds `Authorization: Bearer <CRON_SECRET>` when the
`CRON_SECRET` environment variable is set, which is exactly what the endpoints
check. Hobby accounts are limited to two cron jobs that run once per day; Pro
accounts can add more frequent jobs.

## Platform limits

These are Vercel platform limits, not application bugs:

- **Read-only filesystem.** Only `/tmp` is writable. The bootstrap handles
  Laravel's compiled views and logs. Uploads and backups must use S3
  (`FILESYSTEM_DISK=s3`, `BACKUP_DISK=s3`); files written locally disappear.
- **No queue worker.** `QUEUE_CONNECTION=sync` runs jobs inside the request. If
  you need asynchronous mail or jobs, run a worker on a VPS or another host
  against the same database.
- **No `php artisan schedule:run`.** Use the Vercel cron endpoints above. Long
  backups can exceed the function duration; move `backup:run` to an external
  scheduler if your database is large.
- **4.5 MB request body limit.** This cannot be raised in `vercel.json`. The
  application currently accepts uploads up to 10 MB, so lower
  `MAX_UPLOAD_SIZE` to about 4 MB or use direct-to-S3 uploads. Large backup
  restores through the admin page will fail for the same reason; restore from a
  machine with the Artisan command instead.
- **Function duration.** `vercel.json` sets `maxDuration: 60`. Hobby plans
  support up to 60 seconds; Pro plans can raise this. The daily maintenance
  endpoint may need a longer limit for big backups.
- **Cold starts.** The first request after idle boots Laravel and may be slower.
- **Maintenance mode.** `php artisan down` uses the file driver by default and
  cannot write to the read-only project. Set
  `APP_MAINTENANCE_DRIVER=cache` if you need `down`/`up` on Vercel, and make
  sure the database cache table exists.

## Troubleshooting

- **500 on every page.** Check Vercel's function logs (the app logs to stderr).
  The common causes are a missing `APP_KEY`, the wrong TiDB host/port, a missing
  CA file, or TiDB rejecting the Vercel egress address.
- **419 Page Expired.** Confirm `APP_URL` is the exact HTTPS domain, the session
  cookie is secure, and you are not mixing `www` and the apex domain.
- **Assets 404.** Confirm `public/build` is committed. If you rebuild assets,
  run `npm run build` and commit the output before deploying.
- **`vendor/autoload.php` missing.** Check the build log for Composer failures.
  `vendor` is deliberately excluded from the upload and installed during the
  build.
- **Cron returns 401.** `CRON_SECRET` must be set in Vercel, and the cron request
  must go to the endpoint with the Bearer token that Vercel adds automatically.

## Rolling back

Use **Vercel → Deployments → Promote** to return to the previous deployment.
Database migrations are not automatically rolled back; reverse them with
`php artisan migrate:rollback` only after checking the data impact.
