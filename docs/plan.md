# Digital Health Passport System — Phased Development Plan (RAD)

**Project:** Web-Based Digital Health Passport for Ndirande Community Health Centre
**Institution:** Malawi University of Business and Applied Sciences (MUBAS)
**Methodology:** Rapid Application Development (RAD)
**Stack:** Laravel 12 (PHP 8.2), MySQL, Tailwind/Bootstrap, Alpine.js, simple-qrcode, Spatie Permissions

This plan restates your proposal's methodology and the system blueprint as four RAD phases, each broken into concrete sprints. Every phase includes **why it exists** (the reasoning a supervisor or examiner will look for), **what it delivers**, and **how to know it's done** — so the plan can be followed step by step, not just read.

---

## Phase 1 — Requirements Planning ✅ *Complete*

**Why this phase exists:** RAD starts by fixing scope before any code is written, so that prototyping in Phase 2 has a stable target. For a health system, this phase is also where you protect patients — it's where roles, permissions, and the data model get decided *before* anyone can accidentally expose a record.

**What was delivered:**
- Workflow diagrams and user roles captured in the architecture blueprint (Registration Clerk, Triage Nurse, Clinical Officer/Doctor, Pharmacist, Ward Nurse, Hospital Administrator).
- Database draft translated into 14 migrations.
- Role and permission model seeded (8 roles, 31 permissions via Spatie Permissions).
- Screen sketches turned into a Blade views skeleton.

**Done-when:** Migrations run cleanly, seeders populate roles/permissions/users, and the blueprint document is the agreed source of truth. All verified.

---

## Phase 2 — User Design (Prototyping) 🔄 *In progress*

**Why this phase exists:** RAD's defining feature is that design isn't finalized on paper — it's tested as a working prototype with the actual users (clinicians, nurses) who will use it daily. This is also where your dissertation's qualitative interviews and observations feed back into the interface, so the prototype reflects real workflow, not assumptions.

**What it delivers:**

| Screen | Purpose | Status |
|---|---|---|
| Dashboard | Role-aware stat cards (patients today, pending triage, waiting consultation, admitted, low stock) + quick actions | Placeholder only — needs rebuild |
| Navigation | Role-gated links to Dashboard, Patients, and module-specific screens | Pending |
| Patient Registration | National ID lookup *before* registering, to prevent duplicate records | Create/index/show/edit exist; ID lookup missing |
| Triage / Consultation / Pharmacy / Admission | Functional but not yet refined with user feedback | Exist, iterate during Sprint construction |

**Done-when:** Clinicians/nurses can walk through registration → triage → consultation → pharmacy on the prototype and give feedback that gets incorporated before construction "hardens" the screens.

---

## Phase 3 — Construction (Sprints)

**Why this phase exists:** This is where the prototype becomes a real, testable system. RAD breaks construction into short sprints so each module can be built, demoed, and refined independently rather than attempting one large build — this matches your 8-week construction window in the proposal timeline.

### Sprint 1 — Registration
**Purpose:** This is the entry point for the entire system — every other module depends on a correctly identified, non-duplicated patient record.
- [x] `patients.create/store` with automatic DHP ID generation
- [x] Duplicate National ID guard
- [x] `patients.index` search (name / national ID / DHP ID) with pagination
- [ ] National ID lookup on the create screen (the API exists — wire it into the UI so clerks search *before* registering)
- [ ] Pediatric flow: guardian selection + child record creation, linked to the mother as guardian (per your blueprint's national identification model)

### Sprint 2 — QR Integration
**Purpose:** QR codes are what make return visits fast — without this, every visit degrades back to manual National ID lookup.
- [x] `QrCodeService` (SVG/data URL) + `showQrCode` + `getQrCode` API
- [ ] QR scan/lookup entry point on `patients.index` (paste/scan DHP ID → open record)
- [ ] Verify the QR prints correctly on the patient show page

### Sprint 3 — Triage
**Purpose:** Triage is the clinical safety layer — it's where abnormal vitals should push a patient up the queue before anything worse happens.
- [x] Triage GET form + `triage.save` POST (vitals + priority)
- [x] Vitals recorded with `patient_id`, `recorded_by_user_id`, `recorded_at`
- [x] Priority queue concept defined in the blueprint
- [ ] Implement queue ordering on the dashboard/consultation list
- [ ] Abnormal-vitals auto-prioritization using `Vital::isAbnormal()`

### Sprint 4 — Consultation
**Purpose:** This is the clinical decision-making core — where history, diagnosis, and treatment come together, and where the paper-based system currently fails most (Section 1.1 of your proposal cites fragmented records at referral/consultation as the core problem).
- [x] Consultation GET + `consultation.save` POST (complaint, findings, diagnosis, plan, admission flag)
- [ ] Prescription creation *inside* consultation — currently only notes are saved; add `prescriptions[]` inputs to create real `Prescription` records
- [ ] Wire encounter status transitions: triaged → consultation → completed

### Sprint 5 — Pharmacy & Inventory
**Purpose:** Closes the loop from prescription to medication in hand, and keeps stock counts trustworthy — a stated system objective (reduce duplicate tests/errors, improve medication safety).
- [x] Pharmacy screen lists encounter prescriptions + inventory
- [x] `pharmacy.dispense` sets `status=dispensed`, decrements stock
- [ ] Inventory management UI (`manage_inventory` permission): add/edit/restock, low-stock flags
- [ ] Only show dispense buttons for prescriptions still pending

### Sprint 6 — Inpatient (Admission / Ward / Discharge)
**Purpose:** Covers the "Inpatient Flow" branch of your blueprint's workflow — patients who need more than an OPD visit.
- [x] Admission form creates the encounter + admission record
- [x] Ward round records observations against the latest admission
- [x] Discharge captures final diagnosis, summary, follow-up
- [ ] Ward medication administration log (blueprint §23)
- [ ] Daily progress notes UI

### Sprint 7 — Synchronization
**Purpose:** This is what makes the system usable in the low-connectivity, low-resource environment your proposal specifically names as a constraint — hospitals must keep working when the internet doesn't.
- [x] `sync_queue` table, `SyncQueue` model, `sync.status`/`sync.upload` routes
- [x] `attemptSync()` simulated (95% success)
- [ ] Wire sync enqueue into clinical write actions (patients, encounters, vitals, prescriptions, admissions)
- [ ] Replace the inline simulation with a real background job (`queue:work` or scheduled command)
- [ ] Sync status dashboard widget so staff can see whether they're offline

### Sprint 8 — Reporting
**Purpose:** This is what turns raw records into something the Hospital Administrator can act on, and it's the evidence layer your dissertation will draw on for evaluation.
- [x] Permissions exist: `view_reports`, `generate_reports`, `view_audit_logs`
- [ ] Reports controller + views: patient census, OPD visits, admissions, dispensed meds, inventory
- [ ] Role-gated access (admin / hospital_administrator only)

**Cross-cutting backlog (build alongside the sprints above, not after):**
- User Management (`manage_facility_users`) — no screen exists yet; needed before non-developers can manage staff accounts.
- Facility Management (`manage_facility`) — seed data exists, no UI.
- Audit logging on every clinical write action (blueprint §19) — required for accountability and for your ethics/data-security commitments.

**Done-when:** All `[ ]` items above are checked, and a fresh `migrate:fresh --seed` plus `php artisan test` both pass cleanly.

---

## Phase 4 — Cutover (Testing & Deployment)

**Why this phase exists:** RAD's construction speed only pays off if cutover is disciplined — this is where you prove the system actually works end-to-end, not just module by module, and where your proposal's usability/functionality targets (SUS ≥ 70, 95% pass rate) get measured.

### Testing
- [ ] Feature tests for each new module (users, facilities, inventory, prescriptions, reports)
- [ ] `php artisan test` — all green
- [ ] `migrate:fresh --seed` on a clean database, then verify every route responds
- [ ] Browser smoke test: log in as each of the 9 seeded users and perform a full OPD + inpatient flow
- [ ] Usability testing with ≥5 healthcare workers, targeting SUS ≥ 70 and a 95% functionality pass rate (per your proposal's Objective d)

### Deployment
- [ ] `npm run build` — confirm `public/build/manifest.json` and assets exist
- [ ] `config:cache`, `route:cache`, `view:cache`
- [ ] `APP_ENV=production`, `APP_DEBUG=false` in `.env`
- [ ] HTTPS/TLS on the hosting server
- [ ] Pilot at Ndirande Health Centre, then plan for wider rollout

**Done-when:** The pilot facility can run a real day of OPD + inpatient activity on the system without a developer in the room, and the usability results are documented for your write-up.

---

## Quick reference: environment commands

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
Always use `C:\xampp\php\php.exe` — the PATH `php` (WinGet 8.4) has no `php.ini`/mbstring and will break Laravel.

---

## How the phases map to your proposal's objectives

| Proposal Objective | RAD Phase it's satisfied by |
|---|---|
| (a) Elicit ≥10 requirements via interviews | Phase 1 |
| (b) Design architecture, schema, UI models with supervisor sign-off | Phase 1 → Phase 2 |
| (c) Build 5 core modules (registration, records, immunization*, lab results*, access control) | Phase 3, Sprints 1–8 |
| (d) Usability testing, SUS ≥70, 95% pass rate | Phase 4 |

\* Immunization tracking and lab results aren't yet explicit modules in the current blueprint/sprint list — worth flagging with your supervisor if they're required deliverables, since the current scope (Section 5 of the blueprint) explicitly excludes laboratory systems.