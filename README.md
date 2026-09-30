# Digital Health Passport

Digital Health Passport is a **web-based final-year prototype for Malawi**.
It digitizes selected paper health-passport entries as **verifiable credentials**.
The prototype supports **vaccination** and **laboratory-test** credentials.
It is **not a hospital management system** and **not a full electronic medical record**.

Citizens without smartphones are served through health-worker assisted access:
a health worker searches with National ID or Passport ID, confirms at least
two demographic details, issues or updates credentials, and prints a QR
certificate. National ID is an **identifier, not a password, PIN source,
QR payload, or verification token**.

## Contents

1. [Credential lifecycle](#credential-lifecycle)
2. [Roles](#roles)
3. [Scope and exclusions](#scope-and-exclusions)
4. [Technology stack](#technology-stack)
5. [Setup instructions](#setup-instructions)
6. [Demo accounts](#demo-accounts)
7. [Demo walkthrough](#demo-walkthrough)
8. [Queue and email](#queue-and-email)
9. [Scheduler and cron](#scheduler-and-cron)
10. [Backup and restore](#backup-and-restore)
11. [Testing](#testing)
12. [Known limitations](#known-limitations)

## Credential lifecycle

```text
Register citizen
→ Confirm identity
→ Issue credential
→ Present QR or printed certificate
→ Verify certificate
→ Correct / Revoke / Replace
→ Expire
→ Audit
```

## Roles

| Role | Main capabilities |
|---|---|
| Citizen | View own passport; show active QR; print own certificate |
| Issuer | Search/register citizen; confirm identity; issue/correct/revoke/replace credential; print certificate |
| Verifier | Scan QR or enter credential number; receive minimum required verification result |
| Administrator | Manage DHP users/facilities; review safe audit activity; run/view backup status |

Two access models:

- **Smartphone citizen:** portal account, views own credentials, shows QR code or prints a certificate.
- **Non-smartphone citizen:** visits a participating health facility; a health worker searches with National ID/Passport ID, confirms at least two demographics, issues/updates credentials and prints a QR certificate.

## Scope and exclusions

Excluded functions:

```text
Appointments, queues, triage, clinical notes, diagnoses, prescriptions, pharmacy, inventory, billing, insurance, admissions, beds, wards, theatre, staff scheduling, and full medical history.
```

Detailed medical consultation data is not digitized by this prototype.
Verification discloses the minimum necessary result, never a medical record.

## Technology stack

- Backend: Laravel 11 (PHP), MySQL, Eloquent, Form Requests, Policies/Gates, middleware.
- Frontend: Blade templates with Tailwind CSS and plain vanilla JavaScript. No SPA framework.
- QR generation: server-side with `simplesoftwareio/simple-qrcode`, rendered in Blade.
- Camera QR scanning: `html5-qrcode` library (locally bundled with Vite, loaded only on verification pages).
- Queues: Laravel database queue (`php artisan queue:work`).
- Email: Laravel mail via SMTP; Mailtrap for development notifications.
- Backups: `spatie/laravel-backup` 9.x (database-only, encrypted ZIP archives).
- Required tooling: PHP 8.2+, Composer, Node.js 18+, MySQL with `mysqldump`/`mysql` clients, PHP `zip` extension.

## Setup instructions

```bash
git clone <repository-url>
cd <project-directory>
composer install
npm install
cp .env.example .env        # on Windows: copy .env.example .env
php artisan key:generate
```

Configure `.env` (all from environment, never hardcoded):

- Application and database: `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Mailtrap/default mail: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- Queue connection: `QUEUE_CONNECTION=database`.
- Backup archive password and recipient: `BACKUP_ARCHIVE_PASSWORD`, `BACKUP_EMAIL_RECIPIENT`, `BACKUP_EMAIL_MAX_MB`.
- Backup SMTP: `MAIL_BACKUP_*` (separate mailer so normal mail can stay on Mailtrap).
- Optional: `MYSQLDUMP_PATH` (only needed when `mysqldump` is not on the server PATH).

Then:

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve
php artisan queue:work
php artisan schedule:work
```

On deployment the scheduler may instead run by cron (see below).
To reset demo data at any time: `php artisan migrate:fresh --seed`.

## Demo accounts

Fictional local-development accounts (password `password` for all; local use only, change or remove in deployment):

| Role | Email | Password |
|---|---|---|
| Citizen | citizen@example.test | password |
| Issuer | issuer@example.test | password |
| Verifier | verifier@example.test | password |
| Administrator | admin@example.test | password |

Seeded demo citizens include Yamikani Phiri (National ID `DEMO0001`, no portal account — assisted access) and Tadala Mvula (portal account). Demo credentials cover active, expired, revoked and replaced states.

## Demo walkthrough

See [docs/demo-walkthrough.md](docs/demo-walkthrough.md) for the reproducible end-to-end scenario (register → confirm → issue → print → verify Valid → citizen view → revoke → verify Revoked → audit/backup/Mailtrap checks). What it proves:

```text
- Smartphone and non-smartphone access support.
- National ID is lookup/identity support, not a password.
- QR contains no medical data.
- Verification uses minimum disclosure.
- Revoked/replaced credentials fail verification.
- Audit logs are privacy-safe.
- Backups are encrypted and not publicly downloadable.
```

## Queue and email

```bash
php artisan queue:work
```

Every notification is queued (never sent during the HTTP request). Configure
Mailtrap from `.env` and watch the Mailtrap inbox during the demo.

```text
Queued means the application successfully submitted a notification to Laravel's configured queue. It does not prove that the recipient received, opened, or read the email.
```

Notifications intentionally exclude National ID, passwords, PINs, test results,
vaccine details, QR images/tokens, and full medical details. Citizens without
an email address are skipped silently and never blocked from care.

## Scheduler and cron

Local development:

```bash
php artisan schedule:work
```

Deployment cron (runs every minute):

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

| Task | Schedule |
|---|---|
| Mark credentials expired | Daily 01:00 |
| Notify expiring credentials | Daily 01:15 |
| Encrypted database backup and optional backup email | Daily 02:00 |
| Backup cleanup | Daily 02:30 |
| Backup monitoring | Daily 03:00 |

## Backup and restore

- Database-only backups (no application files, `.env`, logs, or vendor code).
- Archives are AES-256 encrypted with `BACKUP_ARCHIVE_PASSWORD` and stored in non-public `storage/app/backups`. No web download route exists.
- Run manually: `php artisan backup:run-and-email`.
- Archives attach to backup email only below `BACKUP_EMAIL_MAX_MB` (default 20); larger backups send a status note instead. Backup mail uses the separate `smtp_backup` mailer.
- Administrators can trigger and monitor backups from the DHP Backups page.

A restore overwrites the target database and should be performed only by an authorized system operator.

Before restoring in production or a shared environment:

1. Announce a maintenance window.
2. Put the application in maintenance mode:
   ```bash
   php artisan down
   ```
3. Stop or pause queue workers and scheduled jobs to prevent database writes.
4. Make a fresh encrypted backup of the current database where possible.
5. Confirm the archive and target environment.
6. Restore through the guarded command.
7. Validate the restored system.
8. Resume workers/scheduler and bring the application online:
   ```bash
   php artisan up
   ```

Restore with only the archive file name (production requires `--force`):

```bash
php artisan backup:restore example-backup.zip --force
```

Rules: only a safe archive basename is accepted; `BACKUP_ARCHIVE_PASSWORD` is required; copy archives securely into the non-public backup directory; never put archive paths, passwords, or database credentials in tickets or documentation. A manual fallback (extract with password, import the single `.sql` dump with a trusted local MySQL client) is available to trusted operators. `backup_restore_completed` is the durable success indicator once the schema exists; `backup_restore_started` may not persist when restoring an empty database.

## Testing

```bash
php artisan test
```

Coverage:

- Passport data model (identifiers, opaque QR tokens, expiry logic).
- Role access (citizen/issuer/verifier/admin separation, inactive denial).
- Issuer registration, identity confirmation, issuance, correction, revocation, replacement.
- Citizen ownership and privacy (own records only, no clinical/sensitive disclosure).
- QR/manual verification and minimum-disclosure privacy.
- Administration and expiry (user/facility management, audit viewer, idempotent expiry).
- Notifications and queue safety (Mailtrap-safe, no sensitive content).
- Backup/restore (encrypted archives, guarded restore, safe metadata).
- UI, accessibility, and hospital-scope separation.

## Known limitations

- No live integration with Malawi National Registration Bureau, MaHIS/EIR, laboratories, DHIS2, border systems, or National ID services.
- No SMS/USSD or offline mobile app.
- QR verification needs web connectivity to check status and facility activity.
- Printed certificates are supported, but health-worker assisted access remains necessary for citizens without smartphones.
- Email notifications require an email address; no-email citizens are not excluded from service.
- Backup/restore requires `mysqldump`, `mysql`, Zip/PHP extension, controlled server access, and authorized technical operation.
- Before national deployment, the solution would require Ministry of Health governance, data-protection/legal review, hosting/security hardening, key-management policy, and interoperability standards/integration.

---

Legacy hospital-management modules from the original codebase are out of scope for this prototype. The destructive narrowing migration is parked at `database/migrations-parked/` and must not be executed without a separate, backed-up legacy-retirement plan.
