# Security Configuration - Digital Health Passport

This document describes the security controls that are implemented in the
application and the values a production server must use. The step-by-step
release procedure is in `deploy/PRODUCTION-CHECKLIST.md`.

---

## 1. Application key (`APP_KEY`)

The key encrypts sessions, backups and the encrypted clinical text columns. A
backup cannot be read without the exact key that created it.

- Generate the production key once with `php artisan key:generate`.
- Keep a copy of `APP_KEY` off the server, in a password manager or sealed
  envelope.
- Changing the key makes existing backups and encrypted columns unreadable.
  Rotate it only with a planned re-encryption.

---

## 2. Environment

| Setting | Production value | Purpose |
|---------|------------------|---------|
| `APP_ENV` | `production` | Disables development behaviour |
| `APP_DEBUG` | `false` | Prevents stack traces and secrets leaking to users |
| `LOG_LEVEL` | `warning` | Reduces log volume and accidental personal data |
| `BCRYPT_ROUNDS` | `12` | Password hashing cost |
| `SEED_DEMO_DATA` | `false` | No demonstration accounts on a live system |

---

## 3. Sessions

| Setting | Production value | Purpose |
|---------|------------------|---------|
| `SESSION_DRIVER` | `database` (or `redis`) | Server-side session storage |
| `SESSION_ENCRYPT` | `true` | Encrypts the session payload |
| `SESSION_SECURE_COOKIE` | `true` | Cookie is sent over HTTPS only |
| `SESSION_SAME_SITE` | `lax` | Blocks cross-site form posts |
| `SESSION_HTTP_ONLY` | `true` | JavaScript cannot read the cookie |
| `SESSION_DOMAIN` | `null` | Host-only cookie; set an explicit domain only if needed |
| `SESSION_LIFETIME` | `120` | Two-hour idle timeout |

The `sessions` table is created by the default users migration. Sessions are
invalidated when an account or its facility is deactivated.

---

## 4. Security headers

All values live in `config/security.php` and can be overridden in `.env`.

| Header | Value | Protection |
|--------|-------|------------|
| `X-Frame-Options` | `DENY` | Clickjacking |
| `X-Content-Type-Options` | `nosniff` | MIME sniffing |
| `X-XSS-Protection` | `1; mode=block` | Legacy reflected XSS filter |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Referrer leakage |
| `Content-Security-Policy` | Restrictive, see below | XSS and data injection |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains; preload` | HTTPS only (sent on HTTPS, or when `FORCE_HTTPS=true`) |
| `Permissions-Policy` | `camera=(self), microphone=(), geolocation=(), payment=(), usb=()` | Camera limited to this origin (the scanner); other sensors blocked |
| `X-Permitted-Cross-Domain-Policies` | `none` | Legacy cross-domain policy files |

The CSP is `default-src 'self'`, with:

- `script-src 'self' 'unsafe-inline' 'unsafe-eval'`;
- `style-src 'self' 'unsafe-inline'`;
- `img-src 'self' data:`;
- `font-src 'self' data:`;
- `connect-src 'self'`;
- `worker-src 'self'`;
- `manifest-src 'self'`;
- `object-src 'none'`, `base-uri 'self'`, `form-action 'self'`,
  `frame-ancestors 'none'`.

`'unsafe-eval'` is required by the standard Alpine.js build. Removing it
requires switching every view to Alpine's CSP build and re-testing the
interface. `camera=(self)` is required for the passport QR scanner; a bare
`camera=()` silently disables it in Chrome and Safari.

---

## 5. Rate limiting

Limits are defined in `app/Providers/AppServiceProvider.php`; the values come
from `config/security.php` and `.env`.

| Route | Limit | Key |
|-------|-------|-----|
| Sign in | 5 per minute | Lower-cased email + IP |
| Open passport / offline sync | 20 per minute | Signed-in user |
| API and HTTP cron | 120 per minute | User ID, or IP when signed out |
| Registration helpers | 3 per minute | IP |

There is deliberately no global per-IP limiter on every page: a clinic behind
one NAT address would rate-limit its own staff. Route-specific limits cover
the expensive and brute-forceable actions. Certificate verification and
password-reset requests also carry their own throttles.

A failed sign-in lockout is applied after `MAX_FAILED_LOGIN_ATTEMPTS` (5) for
`FAILED_LOGIN_LOCKOUT_MINUTES` (15).

---

## 6. CORS

This is a server-rendered, same-origin application, so CORS is **off by
default** (`CORS_ENABLED=false`). Enable it only when another origin must call
the application, and then:

- list the exact origins in `CORS_ALLOWED_ORIGINS`;
- keep `CORS_ALLOW_CREDENTIALS=false` unless session cookies are required;
- never combine `*` with credentials (the middleware sends the real origin in
  that case).

---

## 7. Trusted proxies

Only the address of the reverse proxy in front of PHP may be trusted. The
default is `127.0.0.1`. Never use a public subnet: a client could spoof
`X-Forwarded-For` and evade rate limits or forge its IP in the audit log.

When nginx terminates TLS on the same host, keep `127.0.0.1` and set
`FORCE_HTTPS=true`.

---

## 8. HTTPS

- `FORCE_HTTPS=true` in production redirects generated URLs to HTTPS and sends
  HSTS.
- `deploy/nginx-dph-system.conf` redirects HTTP to HTTPS.
- `SESSION_SECURE_COOKIE=true` keeps the session cookie off plain HTTP.
- Terminate TLS at nginx (or a load balancer) and pass
  `X-Forwarded-Proto`; the trusted-proxy configuration is required for Laravel
  to see the request as secure.

---

## 9. Uploads and backups

The application has no public file uploads. The only upload is a backup archive
on the administrator's restore form:

- the route requires the `backups.manage` permission;
- the request must be a file and the confirmation checkbox must be accepted;
- the archive is validated and decrypted with `APP_KEY` before restore;
- restore replaces all current data and is recorded in the audit log.

Size limits come from PHP (`upload_max_filesize`, `post_max_size`) and nginx
(`client_max_body_size`, 64 MB in the sample). Backups are encrypted before
they leave the database and should be stored on a durable disk
(`BACKUP_DISK=s3` or an S3 compatible bucket) when the server disk is not
backed up separately.

---

## 10. Secrets to set before deployment

Use `deploy/env.production.example`. At minimum:

```
APP_KEY
APP_URL
DB_DATABASE
DB_USERNAME
DB_PASSWORD
MAIL_FROM_ADDRESS
MAILGUN_DOMAIN
MAILGUN_SECRET
ADMIN_EMAIL
ADMIN_PASSWORD
```

Set `CRON_SECRET` only if the HTTP cron endpoints are used; generate it with:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Never commit `.env`, backups or the application key.

---

## 11. Server requirements

| Component | Requirement |
|-----------|-------------|
| HTTPS | Valid certificate (for example Let's Encrypt) |
| PHP | 8.2+ with the extensions listed in the production checklist |
| MySQL | Dedicated database and non-root application user |
| Redis (optional) | Password protected; `ext-redis` installed on every server |
| S3 (optional) | Private bucket, least-privilege credentials |
| Mail | A real mailer; `log` silently drops resets and reminders |
| Queue worker | Supervisor, or `queue:work --stop-when-empty` from cron |
| Scheduler | `* * * * * php artisan schedule:run` |
| `.env` | Never committed to version control |

The System Health page reports the schema, mail and SMS drivers, durable backup
storage and failed jobs. Check it after every release.

---

## 12. Verification

Before go-live and after every release:

```bash
php artisan test
php artisan migrate --force
php artisan optimize
php artisan about
```

Then confirm `GET /up` returns 200, the security headers are present, the QR
scanner opens the camera, and a test passport can be scanned and printed.
