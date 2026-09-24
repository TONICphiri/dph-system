# Digital Health Passport

A web based health record system for public health facilities in Malawi. Every patient receives one passport number and a printable card with a QR code. The same record follows the patient to any facility that uses the system, and each health worker sees only the part of the record their role needs.

Built with Laravel 12, Blade, PHP 8.2 or newer, Tailwind CSS, Alpine.js and MySQL.

## Contents

1. [Roles and what each one sees](#roles-and-what-each-one-sees)
2. [How a patient moves through the system](#how-a-patient-moves-through-the-system)
3. [Running on a computer with XAMPP](#running-on-a-computer-with-xampp)
4. [Demonstration accounts](#demonstration-accounts)
5. [Running the tests](#running-the-tests)
6. [Hosting on cPanel](#hosting-on-cpanel)
7. [Project structure](#project-structure)
8. [Design rules](#design-rules)
9. [Future features](#future-features)

## Roles and what each one sees

| Role | Main work | Can see |
| --- | --- | --- |
| System Administrator | Registers facilities and their administrators, manages the vaccine list, districts and system settings, checks system health | Facilities, accounts, settings, activity log. No clinical notes |
| Facility Administrator | Registers clerks, nurses, doctors and pharmacists, manages wards, beds and doctor schedules, approves appointments, reads facility reports | Staff, wards, beds, appointments, stock, reports for their own facility |
| Clerk | Registers patients, links children to their mother, checks patients in | Personal details and emergency contacts only |
| Nurse | Records vital signs, allocates beds, keeps the inpatient chart, gives vaccinations | Vital signs, ward information and basic history |
| Doctor | Consults, prescribes, admits and discharges | The full medical record |
| Pharmacist | Dispenses prescriptions and manages medicine stock | Prescriptions and dosage only |
| Patient | Views their own record and their children's records, books appointments, rates visits | Their own record |

Access is enforced on the server by roles and permissions (spatie/laravel-permission), by route middleware and by policies. The sidebar only lists pages the signed in user may open, and any direct attempt to open another page returns an access denied page.

## How a patient moves through the system

**Registration.** The clerk searches first to avoid duplicates. Adults are registered with their National ID. A child under 18 is registered under the mother's record. Every patient receives a passport number and a QR code. Each night the system separates children who have reached the separation age (18 by default, set in System settings) so they get an independent record.

**Outpatient care.** Clerk checks the patient in by scanning the card or entering the passport number. Nurse records vital signs. Doctor consults and prescribes. Pharmacist dispenses, and stock is reduced automatically. The visit is completed and a printable visit report is produced.

**Inpatient care.** The doctor decides to admit. A nurse or the Facility Administrator allocates a ward and bed. Only wards that accept the patient's sex and age are offered. Nurses record vital signs, progress notes and each dose given. The doctor discharges the patient, the bed is released automatically and an inpatient report is produced.

**Patient portal.** Patients book appointments on days when a doctor is scheduled and places remain. The facility approves or declines each request and the patient is notified. After a completed appointment the patient can rate the doctor and say whether they would recommend them.

**Vaccinations and reminders.** Nurses record each dose. The next dose date is calculated from the vaccine schedule and a reminder is created. Reminders are also used for medication refills, including confidential antiretroviral therapy refills. Due reminders are sent every morning.

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

Created when `SEED_DEMO_DATA=true`. Every account uses the password `Password@2026`.

| Role | Email | Facility |
| --- | --- | --- |
| System Administrator | admin@healthpassport.mw | All facilities |
| Facility Administrator | facility@healthpassport.mw | Ndirande Community Hospital |
| Clerk | clerk@healthpassport.mw | Ndirande Community Hospital |
| Nurse | nurse@healthpassport.mw | Ndirande Community Hospital |
| Doctor | doctor@healthpassport.mw | Ndirande Community Hospital |
| Doctor | doctor2@healthpassport.mw | Ndirande Community Hospital |
| Pharmacist | pharmacist@healthpassport.mw | Ndirande Community Hospital |
| Patient | patient@healthpassport.mw | Grace Banda, mother of Daniel Banda |
| Facility Administrator | zomba.admin@healthpassport.mw | Zomba Central Hospital |
| Doctor | zomba.doctor@healthpassport.mw | Zomba Central Hospital |

The demonstration data places patients at every stage: waiting for vital signs, waiting for the doctor, waiting at the pharmacy, admitted to a bed, waiting for a bed, discharged, and a completed outpatient visit with a report.

## Running the tests

The tests use a separate database so your working data is never touched.

1. Create a second database named `health_passport_test`.
2. The tests connect with the MySQL user in `.env`. The test database name is set in `phpunit.xml`.
3. Run:

```bash
php artisan test
```

The tests cover page access for every role, patient registration, the full outpatient journey to dispensing, inpatient admission and bed release, ward suitability rules, role based visibility of records, and appointment booking with approval.

## Hosting on cPanel

1. **Build locally.** Run `composer install --no-dev --optimize-autoloader` and `npm run build`.
2. **Upload.** Compress the project without `node_modules` and `.env`, upload it with File Manager to a folder outside `public_html`, for example `/home/USERNAME/health-passport`, and extract it.
3. **Point the domain to the public folder.** In cPanel, set the document root of the domain to `/home/USERNAME/health-passport/public`. If the document root cannot be changed, copy the contents of `public` into `public_html` and edit the two paths in `public_html/index.php` so they point to `../health-passport/vendor/autoload.php` and `../health-passport/bootstrap/app.php`.
4. **Create the database.** Use MySQL Databases in cPanel to create a database and a user, and give the user all privileges on the database.
5. **Create `.env`.** Copy `.env.example` to `.env` and set at least:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
DB_DATABASE=cpanel_database_name
DB_USERNAME=cpanel_database_user
DB_PASSWORD=strong_password
SEED_DEMO_DATA=false
ADMIN_EMAIL=your_admin_email
ADMIN_PASSWORD=a_strong_password
MAIL_MAILER=smtp
```

   Fill in the mail settings from the cPanel email account if you want email notifications.

6. **Finish the set up** from cPanel Terminal or SSH, inside the project folder:

```bash
php artisan key:generate
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

7. **Add the cron job.** In cPanel Cron Jobs, add one job that runs every minute:

```
* * * * * php /home/USERNAME/health-passport/artisan schedule:run >> /dev/null 2>&1
```

   This sends due reminders at 07:00 and separates adult records at 01:00 every day.

8. **Sign in** as the System Administrator, change the password on the profile page, then register the first facility and its administrator.

After any later update, run `php artisan migrate --force` and repeat the three cache commands.

## Project structure

| Folder | Contents |
| --- | --- |
| `app/Enums` | Roles, permissions and every status used in the system |
| `app/Http/Controllers` | Grouped by area: `Admin`, `Facility`, `Clinical`, `Portal` |
| `app/Http/Requests` | Validation rules and plain error messages for every form |
| `app/Services` | Workflow rules: registration, visits, consultations, admissions, prescriptions, appointments, reports |
| `app/Policies` | Record level access checks |
| `app/Console/Commands` | Daily reminder and age separation tasks |
| `app/Support/Navigation.php` | Builds the sidebar from the user's permissions |
| `database/seeders` | Reference data, the first administrator and optional demonstration data |
| `resources/views` | Blade views grouped the same way as the controllers |
| `tests/Feature` | Access and workflow tests |
| `docs` | System design and requirements document |

**Nothing is hard coded.** Facility types, ownership types, ward types, contact relationships, campaign categories, dosage forms, dosage frequencies, regions, the passport number prefix and the child separation age are all stored in the settings table and edited on the System settings page. Districts and vaccines have their own management pages.

**Error handling.** Every form is validated with clear messages next to each field. Workflow rules that are broken, such as allocating an occupied bed, show a plain message at the top of the page. Missing records, denied access, expired sessions and server errors each have their own page, and unexpected errors are logged without showing technical details to the user.

## Design rules

- Square corners on every container, button and field. The Tailwind configuration sets every border radius to 0.
- No gradients. Gradient utilities are switched off in the Tailwind configuration.
- Plain words instead of abbreviations, for example "antiretroviral therapy clinic".
- One green brand colour for actions and navigation, with amber and red kept for warnings and errors.

## Future features

Listed on the Future features page inside the system and not yet built: mobile money and card payments, medical insurance claims, and an assistant for patient questions.
