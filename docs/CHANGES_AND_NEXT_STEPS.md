# DHP System — Fixes Applied & Roadmap

## 1. Bugs fixed in this patch (apply these first — the app is currently broken without them)

| File | Problem | Fix |
|---|---|---|
| `routes/web.php` | `LabOrderController` used but never imported → **fatal error on every `/lab/orders/*` request** | Added `use App\Http\Controllers\LabOrderController;`; also wrapped the `/test-*` debug routes so they can't be hit in production |
| `app/Http/Requests/UpdateLabOrderRequest.php` | `'result_units' => 'max=50'` — invalid rule syntax, throws `InvalidArgumentException` the moment a lab technician submits results | Changed to `'max:50'`; also made `authorize()` actually check `record_lab_results` instead of always returning `true` |
| `app/Http/Requests/StoreLabOrderRequest.php` | `authorize()` always returned `true` | Now checks `create_lab_orders` permission |
| `resources/views/patients/lab-order-summary.blade.php` | Mixed Jinja-style `{% if %}` with Blade `{@endif}` — not valid Blade, breaks compilation; `@foreach...@else` doesn't render an empty-state | Rewritten with valid Blade and `@forelse/@empty` |
| `resources/views/patients/lab-orders-patient.blade.php` | Stray `>` after `@endcan` renders literally in the page | Removed |

## 2. ⚠️ Do this before any real patient data goes near this system

Delete these files from the repo (they're not part of the Laravel app and sit right next to `public/`):

```
drop_admissions.php
truncate.php
recreate_db.php
list_tables.php
test_phpinfo.php
test_mb.php
test_bootstrap.php
public/test.html
```

Every one of these connects directly to MySQL with hardcoded credentials and has **zero authentication**. If any of them are reachable over HTTP (even accidentally, e.g. a misconfigured document root), `drop_admissions.php` lets an anonymous visitor delete data and `list_tables.php`/`test_phpinfo.php` leak your schema and server config. These are development scratch scripts — remove them, don't just `.gitignore` them, since they're already committed.

```bash
git rm drop_admissions.php truncate.php recreate_db.php list_tables.php test_phpinfo.php test_mb.php test_bootstrap.php public/test.html
git commit -m "security: remove unauthenticated debug/DB scripts before production"
```

## 3. Backup automation + notifications (new in this patch)

- `php artisan backup:run` dumps the database (mysqldump for MySQL, file copy for SQLite), gzips it into `storage/app/backups/`, deletes anything older than 14 days, and **notifies every user who has the `view_audit_logs` permission** (your admins/hospital administrators) by email + in-app notification.
- Scheduled nightly at 02:00 in `routes/console.php`.
- On failure it sends a distinct high-priority "Backup FAILED" email so you find out the same day, not when you need a restore and discover there isn't one.

**To activate:**
1. Run the new migration: `php artisan migrate` (adds the `notifications` table used for the in-app bell).
2. Make sure `mysqldump` is installed on the server/host running the scheduler (XAMPP includes it under `mysql/bin`).
3. Point `MAIL_MAILER` in `.env` at a real transport (currently `log`, so "emails" just go to the log file — fine for dev, not for alerting anyone).
4. Make sure `php artisan schedule:run` is wired into a real cron entry (`* * * * * php artisan schedule:run`) — Task Scheduler on Windows if that's the deployment target, since `Schedule::` does nothing on its own without something ticking it every minute.
5. **Test it manually first:** `php artisan backup:run` and check `storage/app/backups/` and your inbox before trusting it to run unattended.
6. Consider also copying the `.gz` file off-box (S3, another server) — a backup that lives on the same disk as the database doesn't protect you from disk failure, ransomware, or the server itself being lost. This command creates the backup; where it's copied afterward is a deployment decision (rclone/cron, or swap the `File::copy` line for an S3 disk push).

## 4. Security hardening included

- `SecurityHeaders` middleware (registered globally in `bootstrap/app.php`) adds CSP, HSTS, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, and a locked-down `Permissions-Policy`.
- Failed logins and rate-limit lockouts are now written to `audit_logs` via `LogFailedLogin`, so repeated attempts against a real account are visible to admins instead of only silently throttled (the throttle itself already existed in `LoginRequest` — 5 attempts before lockout — this patch just makes the attempts *visible*).
- `URL::forceScheme('https')` in production so password-reset and email-verification links are never generated as `http://` even behind a proxy.

### Still worth doing (not included — needs a decision from you first)

- **Two-factor auth** for admin/doctor roles — Laravel Fortify or a simple TOTP package. High value given this is health data, moderate effort.
- **Encrypt `national_id` at rest** — add Laravel's `encrypted` cast to the `Patient` model's `national_id`/`guardians.national_id` columns. Note: encrypted columns can't be searched with `LIKE`/`WHERE =` directly, so `searchByNationalId` would need a blind index (hash column) alongside it. Flag if you want this — it's a real change to the lookup flow, not a drop-in.
- **Session hardening**: `SESSION_SECURE_COOKIE=true` and shorter `SESSION_LIFETIME` in `.env` for production (currently 120 minutes with no secure-cookie flag set).
- **Force `APP_DEBUG=false` and `APP_ENV=production`** in the deployed `.env` — right now `.env.example` defaults `APP_DEBUG=true`, which would leak stack traces (including query bindings, which could include patient identifiers) to any visitor if left on in production.
- **Rate limit the patient-lookup APIs** (`patients.search.national-id`, `patients.search.dhp-id`) — currently un-throttled, so someone could enumerate National IDs by brute force. Add `->middleware('throttle:30,1')`.

## 5. Workflow & UI roadmap (not yet built — prioritized list)

These are the highest-leverage changes for "faster and more accurate" work, roughly in order of effort vs. impact:

1. **Global patient search bar in the nav** (not just on the patients index page) — right now a clerk has to go to `/patients` first to search; every other page requires navigating back. A persistent Alpine-powered search-as-you-type box in `layouts/navigation.blade.php` hitting the existing `patients.search.dhp-id`/`national-id` endpoints removes a full page load from the most common action in the system.
2. **Keyboard-first triage/consultation forms** — the vitals and consultation forms are plain inputs with no `tabindex` flow or Enter-to-submit; on a busy OPD day this is where seconds add up across dozens of patients. Auto-advance focus, sensible input types (`inputmode="decimal"` for vitals already exist as `type="number"` — good — but the tab order should follow the form flow).
3. **Single navigation bar for role-specific queues** — several nav links point at the same `/patients` index (Triage, Consultation, Pharmacy all link there) which loses the priority-ordered queue the dashboard already builds. Point each role's nav link at a dedicated filtered queue view instead of the generic patient list.
4. **Prescription autocomplete against inventory stock levels** — `consultation.blade.php` already has a `<datalist>` of inventory medication names; extend it to show current stock inline so a clinician doesn't prescribe something that's `out_of_stock` (currently only discovered by the pharmacist later).
5. **Undo/confirmation on destructive actions** — facility/user delete forms use a plain `confirm()` — fine for now, but the medication dispense action has no confirmation at all and directly decrements stock; a mis-click there affects real inventory counts.
6. **Toast notifications instead of full-page flash banners** — every controller redirects with `with('success', ...)`; the current banners work but require a full page reload to appear/disappear. A small Alpine toast component would feel considerably snappier without touching any controller logic.

I'd recommend tackling items 1–3 first since they directly cut the number of clicks/page-loads in the highest-frequency workflows (registration lookup, triage, consultation), which is where "faster and more accurate" pays off the most.

## 6. What I'd need from you to keep going

Given the size of this codebase, tell me which of these to build next and I'll implement it fully (code + migration + tests, matching the existing patterns in the repo):
- The global nav search bar (#1 above) — quick win, ~1 file change plus one new lightweight endpoint.
- Two-factor authentication for privileged roles.
- Encrypted `national_id` with a blind-index search column.
- Role-specific queue views (#3 above) for triage/consultation/pharmacy.
