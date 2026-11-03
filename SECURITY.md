# Security Configuration — Digital Health Passport

## Overview

This document describes all security controls implemented for production deployment.

---

## 1. Application Key (APP_KEY)

**Status: ROTATED**

The previous `APP_KEY` was exposed in chat history and has been rotated.

**Action required:** On the production server, run:
```bash
php artisan key:generate --force
```
Update `.env` with the new key. Never reuse the old key.

---

## 2. Environment Security

| Setting | Value | Purpose |
|---------|-------|---------|
| `APP_ENV` | `production` | Disables debug features |
| `APP_DEBUG` | `false` | Prevents stack trace leakage |
| `LOG_LEVEL` | `warning` | Reduces log verbosity |
| `BCRYPT_ROUNDS` | `12` | Password hashing cost |

---

## 3. Session Security

| Setting | Value | Purpose |
|---------|-------|---------|
| `SESSION_DRIVER` | `database` | Server-side session storage |
| `SESSION_ENCRYPT` | `true` | Encrypts session payload |
| `SESSION_SECURE_COOKIE` | `true` | HTTPS-only cookies |
| `SESSION_SAME_SITE` | `lax` | CSRF protection |
| `SESSION_HTTP_ONLY` | `true` | Prevents JS access to cookies |
| `SESSION_DOMAIN` | `.your-domain.mw` | Scoped to production domain |
| `SESSION_LIFETIME` | `120` | 2-hour session timeout |

---

## 4. Security Headers (All Responses)

| Header | Value | Protection |
|--------|-------|------------|
| `X-Frame-Options` | `DENY` | Clickjacking |
| `X-Content-Type-Options` | `nosniff` | MIME sniffing |
| `X-XSS-Protection` | `1; mode=block` | Reflected XSS (legacy browsers) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Referrer leakage |
| `Content-Security-Policy` | Restrictive | XSS, data injection |
| `Strict-Transport-Security` | `max-age=31536000` | HTTPS enforcement (HSTS) |
| `Permissions-Policy` | Restrictive | Camera, mic, geolocation, etc. |

---

## 5. Rate Limiting

| Endpoint | Limit | Window | Key |
|----------|-------|--------|-----|
| Login | 5 attempts | per minute | IP + username/email |
| API | 60 requests | per minute | IP |
| Register | 3 attempts | per minute | IP |

Laravel's built-in `RateLimiter` is configured in `bootstrap/app.php`. Login failures trigger lockout after `MAX_FAILED_LOGIN_ATTEMPTS` (5) for `FAILED_LOGIN_LOCKOUT_MINUTES` (15).

---

## 6. CORS

| Setting | Value |
|---------|-------|
| Enabled | `true` |
| Allowed Origins | `https://your-domain.mw` (replace with real domain) |
| Allowed Methods | GET, POST, PUT, PATCH, DELETE, OPTIONS |
| Allowed Headers | Origin, Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN |
| Allow Credentials | `true` |
| Max Age | 3600 seconds |

---

## 7. Trusted Proxies

Instead of trusting all proxies (`*`), only specific ranges are trusted:

```
127.0.0.1, 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
```

Replace with your actual load balancer/CDN IPs in production.

---

## 8. HTTPS Enforcement

- `FORCE_HTTPS=true` in `.env`
- Nginx config redirects HTTP → HTTPS (`deploy/nginx-dph-system.conf`)
- HSTS header added to all HTTPS responses

---

## 9. File Upload Security

| Setting | Value |
|---------|-------|
| Max file size | 10 MB |
| Allowed MIME types | image/jpeg, image/png, image/webp, application/pdf |
| Storage disk | S3 (not local) |

---

## 10. Secrets to Replace Before Deployment

Every `change-me-` value in `.env` must be replaced:

```
DB_PASSWORD
ADMIN_PASSWORD
REDIS_PASSWORD
MAILGUN_SECRET
MAILGUN_DOMAIN
MAIL_FROM_ADDRESS
AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
AWS_BUCKET
CRON_SECRET
APP_URL
SESSION_DOMAIN
CORS_ALLOWED_ORIGINS
```

Generate `CRON_SECRET` with:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

---

## 11. Server-Side Security Requirements

| Component | Requirement |
|-----------|-------------|
| HTTPS | Valid SSL certificate (Let's Encrypt) |
| Redis | Password-protected |
| MySQL | Strong root password, non-root app user |
| S3 | Private bucket, IAM least-privilege |
| Mailgun | Verified domain, SPF/DKIM records |
| Queue Worker | Running via Supervisor |
| Cron | `* * * * * php artisan schedule:run` |
| .env | Never committed to git |

---

## 12. Files Changed

| File | Action |
|------|--------|
| `.env` | Production security values |
| `bootstrap/app.php` | Security middleware + rate limiters |
| `config/app.php` | Added `trusted_proxies` |
| `config/security.php` | New security config |
| `config/cors.php` | New CORS config |
| `app/Http/Middleware/SecurityHeadersMiddleware.php` | New |
| `app/Http/Middleware/RateLimitMiddleware.php` | New |
| `app/Http/Middleware/CorsMiddleware.php` | New |
| `deploy/nginx-dph-system.conf` | HTTPS Nginx config |
| `deploy/supervisor-dph-system.conf` | Queue worker |
| `deploy/cron-dph-system.txt` | Scheduler cron |
