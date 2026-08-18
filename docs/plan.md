# Digital Health Passport System — RAD Implementation Plan

**Prepared for:** Malawi University of Business and Applied Sciences (MUBAS)
**Project:** Nationwide Digital Health Passport System
**Methodology:** Rapid Application Development (RAD)
**Stack:** Laravel 12 (PHP 8.2), MySQL, Tailwind/Bootstrap, Alpine.js, QR (simple-qrcode), Spatie Permissions

---

## 0. Current Project Status (verified)

Everything below has been verified working before this plan was written:

- [x] All 64 PHP files lint clean (using `C:\xampp\php\php.exe`)
- [x] `php artisan route:list` loads all 51 routes
- [x] 14 migrations run cleanly (`migrate:fresh --force`) on MySQL `dhp_system`
- [x] Seeders run: 3 facilities, 9 users, 31 permissions, 8 roles
- [x] All 25 PHPUnit tests pass
- [x] App serves on `http://127.0.0.1:8000`; login page + error pages render
- [x] Vite build assets present in `public/build/`

**Key environment notes:**
- Use XAMPP PHP: `C:\xampp\php\php.exe` (the `php` on PATH is WinGet 8.4 with no `php.ini`/`mbstring` — breaks Laravel).
- MySQL has no Windows service; start manually:
  `Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini"`
- Run the server: `C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000`

---

## RAD Phase 1 — Requirements Planning  ✅ (DONE)

Source of truth: `system-description-architecture-blueprint.md`

- [x] Workflow diagrams / user roles captured in blueprint
- [x] Database draft implemented as 14 migrations
- [x] Role & permission model seeded (8 roles, 31 permissions)
- [x] Screen sketches → translated into Blade views skeleton

---

## RAD Phase 2 — User Design  (PROTOTYPING)

Feedback loop with clinicians/nurses. Prototype screens should be functional before construction hardens them.

### 2.1 Dashboard Prototype (currently placeholder "You're logged in!")
- Replace `resources/views/dashboard.blade.php` placeholder with:
  - Role-aware stat cards (patients today, pending triage, waiting consultation, admitted, low stock)
  - Quick-action buttons per role (Register Patient, Triage, Consultation, Pharmacy, Admissions)
- Nav (`resources/views/layouts/navigation.blade.php`) gains links: Dashboard, Patients, plus role-gated links.

### 2.2 Patient Registration Prototype
- Already exists: `patients.create`, `patients.index`, `patients.show`, `patients.edit`
- Add **National ID lookup** on the create screen (search before registering to avoid duplicates).

### 2.3 Triage / Consultation / Pharmacy / Admission prototypes
- Screens exist; iterate layout with user feedback during construction.

---

## RAD Phase 3 — Construction (SPRINTS)

### Sprint 1 — Registration
- [x] `patients.create/store` — register with DHP ID generation (`Patient::generateDhpId()`)
- [x] Duplicate National ID guard
- [x] `patients.index` with search (name / national_id / dhp_id), pagination
- [ ] **Add UI for National ID lookup before registration** (`searchByNationalId` exists as API; add to create screen)
- [ ] Pediatric flow: guardian selection + child record creation (link to mother as guardian)

### Sprint 2 — QR Integration
- [x] `QrCodeService` (SVG/data URL) + `showQrCode` + `getQrCode` API
- [ ] Add QR scan/lookup entry on `patients.index` (paste/scan DHP ID → open record)
- [ ] Verify QR prints on patient show page

### Sprint 3 — Triage
- [x] `patients/triage` GET form + `triage.save` POST (vitals + priority)
- [x] Vitals written with `patient_id`, `recorded_by_user_id`, `recorded_at`
- [x] Priority queue concept in blueprint → implement queue ordering on dashboard/consultation list
- [ ] Abnormal-vitals auto-prioritization (uses `Vital::isAbnormal()`)

### Sprint 4 — Consultation
- [x] `consultation` GET + `consultation.save` POST (complaint, findings, diagnosis, plan, requires_admission)
- [ ] **Prescription creation inside consultation** — `saveConsultation` currently records notes only; add prescriptions[] inputs → `Prescription` records (medication_name, dose, frequency, quantity, duration, prescribed_at)
- [ ] Encounter status transitions wired (triaged → consultation → completed)

### Sprint 5 — Pharmacy & Inventory
- [x] `pharmacy` screen lists encounter prescriptions + inventory
- [x] `pharmacy.dispense` updates prescription `status=dispensed` + decrements `current_stock` + `updateStatus()`
- [ ] **Inventory management UI** (`manage_inventory` permission): add/edit/restock items, low-stock flags
- [ ] Only show dispense buttons for pending prescriptions

### Sprint 6 — Inpatient (Admission / Ward / Discharge)
- [x] `admission` form + `admission.create` (creates encounter + admission, correct schema)
- [x] `ward.round` records observations against latest admission
- [x] `discharge` POST (final diagnosis, summary, follow-up)
- [ ] Ward medication administration log (per blueprint §23)
- [ ] Daily progress notes UI

### Sprint 7 — Synchronization
- [x] `sync_queue` table + `SyncQueue` model + `sync.status` / `sync.upload` routes
- [x] `attemptSync()` simulation (95% success)
- [ ] Wire sync enqueue into clinical write actions (patients, encounters, vitals, prescriptions, admissions)
- [ ] Background sync job (`queue:work` / scheduled command) replacing the inline simulation
- [ ] Sync status dashboard widget

### Sprint 8 — Reporting
- [x] Permissions exist: `view_reports`, `generate_reports`, `view_audit_logs`
- [ ] Reports controller + views: patient census, OPD visits, admissions, dispensed meds, inventory
- [ ] Role-gated access (admin / hospital_administrator)

---

## RAD Phase 4 — Cutover (TESTING & DEPLOYMENT)

### Testing
- [ ] Feature tests for each new module (users, facilities, inventory, prescriptions, reports)
- [ ] Run: `C:\xampp\php\php.exe artisan test` (all green)
- [ ] `php artisan migrate:fresh --seed` on a clean database; verify every route responds
- [ ] Browser smoke test: login as each of the 9 seeded users → perform full OPD + inpatient flow

### Deployment
- [ ] `npm run build` → confirm `public/build/manifest.json` + assets
- [ ] `php artisan config:cache`, `route:cache`, `view:cache`
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false` in `.env`
- [ ] HTTPS/TLS on the hosting server
- [ ] Pilot at one facility, then national rollout

---

## Cross-cutting Backlog (below RAD core sprints)

- **User Management** (`manage_facility_users`): UserController + `users/` views (list, create, edit, assign role + facility). No user-management screen exists yet.
- **Facility Management** (`manage_facility`): FacilityController + `facilities/` views. Seed data exists; no UI.
- **Audit logging**: capture user/time/facility/device per blueprint §19 — on clinical write actions.

---

## How to run each verification command (XAMPP)

```powershell
# Start MySQL
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini"

# Migrate + seed
C:\xampp\php\php.exe artisan migrate:fresh --seed --force

# Serve
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000

# Tests
C:\xampp\php\php.exe artisan test
```

---

## Status legend
- [x] Verified working today
- [ ] Remaining work item (build during the applicable sprint)