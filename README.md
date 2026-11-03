# Digital Health Passport

A web based health passport. Each person carries a card with a QR code. A health worker scans the card to open the passport, adds what happened at the visit, and the person can read their own passport and see who opened it.

It is not a hospital management system or a patient record system. It has no wards, beds, pharmacy stock, appointments or billing. Tests are recorded as a test name and a result only. Dispensing only marks prescribed medicines as handed over. It keeps only the essentials that should travel with a person from one facility to the next.

## Contents

- Roles
- How a visit works
- Running on a computer with XAMPP
- Demonstration accounts
- Running the tests
- Hosting on cPanel
- Production deployment
- Backups
- Design rules

## Roles

| Role | What the role does | What it cannot do |
|---|---|---|
| System Administrator | Registers facilities and facility administrators, manages the vaccine list, districts and settings, reads the activity log | See any passport |
| Facility Administrator | Registers the health workers of one facility, updates the facility profile, reads facility reports and activity | See any passport |
| Health Worker | Issues passports, opens a passport by scanning the card, records the stages of a visit (reception, triage, consultation, dispensing), vaccinations and reminders | Manage accounts or settings |
| Patient | Reads their own passport and their children's, shows the QR card, sees who opened the passport | Change records |

## How a visit works

A health worker's position decides which station of a visit they record: a **clerk** starts the visit at reception and notes the reason, a **nurse** or **doctor** records triage and vital signs, the **doctor** records consultation, diagnoses and prescriptions, and the **pharmacist** marks the prescribed medicines as handed over. Positions are set by the Facility Administrator when a worker's account is created. A worker without a position can issue passports, vaccinate and set reminders but cannot record a visit.

1. **New holder.** The health worker issues a passport. No card exists yet, so there is nothing to scan. The passport number and QR card are produced, and the passport is open for the worker who issued it.
2. **Every later visit begins with a scan.** The health worker opens "Open a passport" and scans the QR code on the card. Without the card, the passport number or National ID and the date of birth must match.
3. **The passport stays open for a limited time** (30 minutes by default, set in System settings). While it is open, and only then, the health worker can read the passport and record visit stages, vaccinations and reminders. It can be closed earlier.
4. **The visit has four stages**, each credited to the worker who recorded it. The pharmacist's handover of the last medicine completes and locks the visit, after which it can no longer be changed. A consultation with no prescribed medicines completes the visit at once.
5. **Every opening is recorded.** The holder sees who opened the passport, when, and how.
6. **Children** are linked through the mother's passport, which must be open on the same device.

## Running on a computer with XAMPP

Requirements: XAMPP with PHP 8.2 or newer, Composer, Node.js 18 or newer.

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open phpMyAdmin at http://localhost/phpmyadmin and create a database named `health_passport` with collation `utf8mb4_unicode_ci`.
3. In a terminal inside the project folder, run:

```bash
composer install
npm install
npm run build
copy .env.example .env        # on macOS or Linux: cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

4. Open http://localhost:8000 and sign in with one of the accounts below.

The `.env.example` file already uses the XAMPP defaults: user `root` with no password. Change `DB_USERNAME` and `DB_PASSWORD` if your MySQL is set up differently.

To start again with fresh demonstration data at any time:

```bash
php artisan migrate:fresh --seed
```

To run the daily reminder and age separation tasks by hand:

```bash
php artisan reminders:send
php artisan patients:separate-adults
```

## Demonstration accounts

Created when `SEED_DEMO_DATA=true`. Every demonstration account uses the value of `ADMIN_PASSWORD` from `.env`. Never enable demonstration data on a public server.

| Role / Position | Email | Belongs to |
|---|---|---|
| System Administrator | admin@healthpassport.mw | All facilities |
| Facility Administrator | facility@healthpassport.mw | Ndirande Community Hospital |
| Health Worker (Doctor) | healthworker@healthpassport.mw | Ndirande Community Hospital |
| Health Worker (Nurse) | healthworker2@healthpassport.mw | Ndirande Community Hospital |
| Health Worker (Clerk) | clerk@healthpassport.mw | Ndirande Community Hospital |
| Health Worker (Pharmacist) | pharmacist@healthpassport.mw | Ndirande Community Hospital |
| Patient | patient@healthpassport.mw | Grace Banda, mother of Daniel Banda |
| Facility Administrator | zomba.admin@healthpassport.mw | Zomba Central Hospital |
| Health Worker (Doctor) | zomba.healthworker@healthpassport.mw | Zomba Central Hospital |

## Running the tests

The test suite uses an in-memory SQLite database, so it never touches your working data and needs no extra database server. Configuration is in `phpunit.xml`.

```bash
php artisan test
```

The tests cover page access for every role, the scan gate (a passport stays closed until the card is scanned, and closes again when it expires), the identity check used when a card is missing, the staged visit workflow from reception to handover, linking a child through the mother's passport, and the access history shown to the holder.

A full run takes about five minutes on a laptop. Individual areas can be run faster:

```bash
php artisan test tests/Feature/ImmunisationStatusTest.php
```

## Hosting on cPanel

1. **Build locally.** Run `composer install --no-dev --optimize-autoloader` and `npm run build`.
2. **Upload.** Compress the project without `node_modules` and `.env`, upload it with File Manager to a folder outside `public_html`, for example `/home/USERNAME/health-passport`, and extract it.
3. **Point the domain to the public folder.** In cPanel, set the document root of the domain to `/home/USERNAME/health-passport/public`. If the document root cannot be changed, copy the contents of `public` into `public_html` and edit the two paths in `public_html/index.php` so they point to `../health-passport/vendor/autoload.php` and `../health-passport/bootstrap/app.php`.
4. **Create the database.** Use MySQL Databases in cPanel to create a database and a user, and give the user all privileges on the database.
5. **Create `.env`.** Use `deploy/env.production.example` as the checklist and set at least:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=            # run: php artisan key:generate
APP_URL=https://your-domain
DB_DATABASE=cpanel_database_name
DB_USERNAME=cpanel_database_user
DB_PASSWORD=strong_password
SEED_DEMO_DATA=false
ADMIN_EMAIL=your_admin_email
ADMIN_PASSWORD=a_strong_password
MAIL_MAILER=smtp
SESSION_SECURE_COOKIE=true
FORCE_HTTPS=true
```

   Leave `ASSET_URL` empty unless a CDN actually serves `/build`. Fill in the mail settings from the cPanel email account, and do not leave `MAIL_MAILER=log` on a live server.

6. **Finish the set up** from cPanel Terminal or SSH, inside the project folder:

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

7. **Add the cron jobs.** In cPanel Cron Jobs, add one job that runs every minute:

```
* * * * * php /home/USERNAME/health-passport/artisan schedule:run >> /dev/null 2>&1
```

   If `QUEUE_CONNECTION=database` (the default in the production template), add a second job that drains the queue each minute:

```
* * * * * php /home/USERNAME/health-passport/artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

   Alternatively set `QUEUE_CONNECTION=sync` on a shared host, but then a slow mail provider delays the request that sends the message.

8. **Sign in** as the System Administrator, change the password on the profile page, then register the first facility and its administrator. Open **System Health** and confirm there are no red rows.

After any later update, run `php artisan migrate --force`, then `php artisan optimize` and `php artisan queue:restart`.

## Production deployment

The application is a normal Laravel monolith and does not need Vercel. Two
supported layouts are documented:

- **nginx + PHP-FPM on a VPS** - use `deploy/nginx-dph-system.conf` and
  `deploy/supervisor-dph-system.conf`.
- **cPanel shared hosting** - use the steps above.

Use `deploy/env.production.example` as the environment checklist and follow
`deploy/PRODUCTION-CHECKLIST.md` before the first request and on every release.
The important points are:

- Serve only `public/` and keep the rest of the project outside the web root.
- Set `APP_ENV=production`, `APP_DEBUG=false`, a real `APP_KEY`, a real
  `APP_URL` and `SEED_DEMO_DATA=false`.
- Use a real mailer; `MAIL_MAILER=log` silently drops password resets and
  reminders.
- Keep `TRUSTED_PROXIES` to the proxy address, never a public subnet.
- Run the scheduler every minute and a queue worker (or set
  `QUEUE_CONNECTION=sync` on a host that cannot run one).
- Run `php artisan migrate --force` before the new code takes traffic and
  `php artisan optimize` after it.

Hosts without a system scheduler can call the protected endpoints
`/api/cron/reminders` and `/api/cron/maintenance` with
`Authorization: Bearer <CRON_SECRET>`. Leave `CRON_SECRET` empty to disable
them.

## Backups

The System Administrator opens **Backups** to make a backup now, download any copy, or delete one. A copy is also made every day at the time set by `BACKUP_TIME` (default 02:00), and the administrators are notified when it finishes or fails. How many days copies are kept, and whether the daily backup runs, are on the Settings page. The newest three copies are always kept.

A backup file is compressed and encrypted with `APP_KEY`, so it cannot be read without that key. **Keep a copy of `APP_KEY` somewhere safe, away from the server.** A backup cannot be restored with a different key.

Set `BACKUP_DISK=s3` (or another S3 compatible bucket such as Cloudflare R2) when the server disk is temporary or not separately backed up. The backup is written to temporary storage and then streamed to the configured disk, so the same code works on cPanel, a VPS and an object store.

To restore on a working installation (same migrations, same `APP_KEY`):

```bash
php artisan backup:restore dhp-20261004-020000.dhpbak
```

To restore on a new server, install the project, copy the old `APP_KEY` into `.env`, run `php artisan migrate --force`, then pass either the full path to the backup file or upload it to the configured backup disk under `backups/` and run the command above. Restoring replaces all current data.

## Working without a connection

When the connection drops after the Open a passport page has loaded, or the page was opened once before while online, the health worker can still scan cards. Each card is kept in that browser, and visit notes or a vaccination can be typed for it. When the server can be reached again, the passport is opened by the scan first, then the notes are added, so the access log, permissions and validation work exactly as usual. If the notes break a rule, the normal form opens with the text kept so it can be corrected.

A card on its own is kept for the passport session time on the Settings page. Notes are kept for **Hours a visit typed offline is kept on the device** (24 by default), then removed. Nothing is kept for a different worker, and the offline page is removed from the device when the worker signs out. The device must have opened the Open a passport page online at least once after signing in.

Reading a passport offline is not possible by design: health information is never stored on the device.

## Text messages

Set `SMS_DRIVER` to `twilio` or `africastalking` and fill in that provider's values in `.env`, then switch on text messages on the Settings page. Messages are sent only to passport holders who agreed, and never contain clinical details. `SMS_DRIVER=log` writes messages to the log instead of sending them.

## Design rules

- Access is decided on the server by role permissions and by whether the passport is open.
