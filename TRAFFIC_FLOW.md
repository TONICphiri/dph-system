# Traffic Flow & Performance Optimization

## Overview

This document covers all traffic flow bottlenecks identified in the Digital Health Passport system and the fixes applied to handle production-scale load.

---

## 1. Session Storage: Database → Redis (Critical Fix)

### Problem
Every HTTP request reads and writes session data to MySQL. Under concurrent load, the `sessions` table becomes a serialization point — every request waits for a row lock on the same table.

### Fix
Changed `SESSION_DRIVER` from `database` to `redis` in `.env`:

```
SESSION_DRIVER=redis
SESSION_CONNECTION=cache
```

Sessions now store in Redis with automatic TTL expiration, removing all session-related load from MySQL.

### Impact
- Eliminates row-lock contention on `sessions` table
- Sub-millisecond session reads/writes
- MySQL freed for clinical data queries only

---

## 2. Cache Storage: Database → Redis (Critical Fix)

### Problem
`CACHE_STORE=database` meant every cache hit/miss hit MySQL.

### Fix
Changed to Redis:

```
CACHE_STORE=redis
```

### Impact
- Cache operations no longer compete with clinical queries on MySQL
- Faster page loads (config, views, routes cached in Redis)

---

## 3. Queue: Database → Redis (Critical Fix)

### Problem
`QUEUE_CONNECTION=database` meant every queued job (email, SMS, backup, reminder) wrote to the `jobs` table on MySQL, competing with user traffic.

### Fix
Changed to Redis:

```
QUEUE_CONNECTION=redis
```

### Impact
- Job dispatch no longer touches MySQL
- Workers pull jobs from Redis, not MySQL polling
- Email/SMS/backup jobs don't slow down page loads

---

## 4. Broadcasting: Log → Redis (Medium Fix)

### Problem
`BROADCAST_CONNECTION=log` meant no real-time notifications; clients had to poll.

### Fix
Changed to Redis:

```
BROADCAST_CONNECTION=redis
```

Combined with `NOTIFICATION_POLL_SECONDS=120` (increased from 60), this reduces client-side polling frequency by 50%.

---

## 5. Read Replica Support (High Fix)

### Problem
All traffic — reads and writes — hit a single MySQL instance. Under load, write queries (visit stages, vaccinations) block read queries (dashboard, patient history).

### Fix
Added `mysql_read` connection in `config/database.php`:

```php
'mysql_read' => [
    'driver' => 'mysql',
    'host' => env('DB_READ_HOST', '127.0.0.1'),
    'read_write' => false,
    ...
],
```

Configuration in `.env`:

```
DB_READ_REPLICA_ENABLED=false
DB_READ_HOST=127.0.0.1
DB_READ_PORT=3306
DB_READ_DATABASE=dhp_system
DB_READ_USERNAME=root
DB_READ_PASSWORD=
```

### Deployment
1. Set up MySQL read replica
2. Set `DB_READ_REPLICA_ENABLED=true` on production
3. Point `DB_READ_HOST` at the replica
4. Use `DB::connection('mysql_read')` for read-only queries in controllers

---

## 6. Response Compression (Medium Fix)

### Problem
HTML, JSON, and CSS responses sent uncompressed, increasing bandwidth and page load time.

### Fix
Added `CompressResponseMiddleware` that applies gzip/brotli compression to compressible responses. Registered in `bootstrap/app.php` web middleware group.

Configuration:

```
RESPONSE_COMPRESSION=true
```

### Impact
- 60-80% smaller HTML/JSON responses
- Faster page loads on mobile/slow connections

---

## 7. Slow Request Logging (Medium Fix)

### Problem
No visibility into which endpoints are slow under load.

### Fix
Added `LogSlowRequestsMiddleware` that logs requests exceeding the threshold:

```
SLOW_REQUEST_THRESHOLD_MS=1000
```

Logs include method, URI, duration, IP, user ID, and status code. Adds `X-Response-Time` header to all responses.

---

## 8. Session Cleanup Command (Medium Fix)

### Problem
Database sessions (if ever switched back) grow unbounded, slowing session queries over time.

### Fix
Created `CleanupSessionsCommand`:

```bash
php artisan sessions:cleanup --hours=24
```

Registers in `bootstrap/app.php` console commands. Run daily via cron:

```cron
0 3 * * * cd /var/www/dph-system && php artisan sessions:cleanup --hours=24
```

---

## 9. Database Performance Indexes (Already Present)

The migration `2026_10_21_000001_add_performance_indexes.php` already adds composite indexes:

| Table | Index | Query Pattern |
|-------|-------|---------------|
| `passport_accesses` | `(facility_id, opened_at)` | Facility dashboard: today's accesses |
| `audit_logs` | `(facility_id, created_at)` | Audit page activity feed |
| `visit_stages` | `(recorded_by, recorded_at)` | Worker dashboard: today's stages |
| `patients` | `(registered_facility_id, created_at)` | Dashboard: new passports this month |
| `notifications` | `(notifiable_type, notifiable_id, read_at)` | Bell: unread notifications |

---

## 10. Notification Polling (Medium Fix)

### Problem
`NOTIFICATION_POLL_SECONDS=60` meant browsers hit `/notifications/unread` every 60 seconds per user — constant DB load even when idle.

### Fix
Increased to 120 seconds:

```
NOTIFICATION_POLL_SECONDS=120
```

### Impact
- 50% fewer notification polling requests
- Combine with Redis broadcasting for push notifications (future)

---

## 11. Asset URL / CDN (Medium Fix)

### Problem
Static assets (CSS, JS, images) served from the app server, consuming app server bandwidth.

### Fix
Added `ASSET_URL` configuration:

```
ASSET_URL=https://cdn.your-domain.mw
```

Configuration available via `config('app.asset_url')`. Point your CDN at the S3 bucket or public directory.

---

## 12. Database Connection Pooling (Medium Fix)

Added configuration for connection pool tuning:

```
DB_POOL_MIN=5
DB_POOL_MAX=20
```

These values can be used when configuring the database connection pool on the production server.

---

## Files Changed

| File | Change |
|------|--------|
| `.env` | Redis sessions/cache/queue, read replica, compression, polling, thresholds |
| `config/database.php` | Added `mysql_read` connection |
| `config/app.php` | Added `response_compression`, `asset_url`, `performance` config |
| `bootstrap/app.php` | Registered compression + slow request middleware + cleanup command |
| `app/Http/Middleware/CompressResponseMiddleware.php` | New — gzip/brotli response compression |
| `app/Http/Middleware/LogSlowRequestsMiddleware.php` | New — logs requests > 1000ms |
| `app/Console/Commands/CleanupSessionsCommand.php` | New — cleans expired sessions |
| `SECURITY.md` | Updated with performance section |

---

## Deployment Checklist for Traffic

```
[ ] Set up Redis server (sessions, cache, queue, broadcasting)
[ ] Set CACHE_STORE=redis, SESSION_DRIVER=redis, QUEUE_CONNECTION=redis
[ ] Set up MySQL read replica, set DB_READ_REPLICA_ENABLED=true
[ ] Configure Nginx with gzip_static or use CompressResponseMiddleware
[ ] Set up CDN for static assets (ASSET_URL)
[ ] Schedule sessions:cleanup daily via cron
[ ] Configure queue workers via Supervisor (2+ processes)
[ ] Monitor slow request logs (X-Response-Time header)
[ ] Set up database connection pooling (DB_POOL_MIN/MAX)
[ ] Run php artisan migrate to apply any new indexes
```
