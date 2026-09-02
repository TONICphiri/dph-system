    # Digital Health Passport System — Phased Development Plan (RAD)

**Project:** Web-Based Digital Health Passport for Ndirande Community Health Centre
**Institution:** Malawi University of Business and Applied Sciences (MUBAS)
**Methodology:** Rapid Application Development (RAD)
**Stack:** Laravel 12 (PHP 8.2), MySQL, Tailwind/Bootstrap, Alpine.js, simple-qrcode, Spatie Permissions

This plan restates your proposal's methodology and the system blueprint as four RAD phases, each broken into concrete sprints. Every phase includes **why it exists** (the reasoning a supervisor or examiner will look for), **what it delivers**, and **how to know it's done** — so the plan can be followed step by step, not just read.

### Centralized design principle and efficiency objective

The system must be designed as a centralized digital health passport for Malawi, not as a collection of isolated departmental tools. The patient record should be created once, linked to a unique DHP ID, and then reused across registration, triage, consultation, pharmacy, admission, and discharge. This makes the platform different from conventional Electronic Medical Records (EMRs) and Electronic Health Records (EHRs), which are often institution-centric, department-based, and designed mainly for administrative or clinical documentation rather than a portable patient journey across the full care pathway.

Unlike typical EMR/EHR systems, this project is specifically positioned as a patient-centered, facility-linked, and journey-based health passport. It is designed to follow the patient from arrival at the health centre to discharge, while supporting continuity of care, faster retrieval of historical information, and a simpler, more visible workflow for low-resource clinic settings. It is not a generic replacement for all hospital software; it is a focused digital passport system built to reflect Malawi's practical healthcare reality and to improve queue flow, continuity, and staff efficiency.

To strengthen the value proposition, the interface must also be optimized for efficiency. The user experience should reduce waiting time, minimize duplicate data capture, and make the next step in the patient journey obvious to staff. In practical terms, a clerk, nurse, clinician, pharmacist, and administrator should be able to navigate the same patient record with fewer clicks and clearer task flow, rather than re-entering the same history in separate systems. This is central to the system's adoption, usability, and impact at Ndirande Health Centre.

---

## Phase 1 — Requirements Planning ✅ *Complete*

**Why this phase exists:** RAD starts by fixing scope before any code is written, so that prototyping in Phase 2 has a stable target. For a health system, this phase is also where you protect patients — it's where roles, permissions, and the data model get decided *before* anyone can accidentally expose a record.

**What was delivered:**
- Workflow diagrams and user roles captured in the architecture blueprint (Registration Clerk, Triage Nurse, Clinical Officer/Doctor, Pharmacist, Ward Nurse, Hospital Administrator).
- Database draft translated into 14 migrations.
- Role and permission model seeded (8 roles, 31 permissions via Spatie Permissions).
- Screen sketches turned into a Blade views skeleton.
- Centralized patient journey defined around a single DHP ID and a shared patient timeline across the facility.
- Interface efficiency requirements captured: fewer duplicate steps, role-based dashboards, and streamlined queues for registration, triage, consultation, pharmacy, and discharge.

**Done-when:** Migrations run cleanly, seeders populate roles/permissions/users, the blueprint document is the agreed source of truth, and the system design clearly demonstrates centralization and efficiency gains over fragmented manual or siloed digital processes. All verified.

---

## Phase 2 — User Design (Prototyping) 🔄 *In progress*

**Why this phase exists:** RAD's defining feature is that design isn't finalized on paper — it's tested as a working prototype with the actual users (clinicians, nurses) who will use it daily. This is also where your dissertation's qualitative interviews and observations feed back into the interface, so the prototype reflects real workflow, not assumptions.

**What it delivers:**

| Screen | Purpose | Status |
|---|---|---|
| Dashboard | Role-aware stat cards (patients today, pending triage, waiting consultation, admitted, low stock) + quick actions | ✅ Built — `DashboardController` + stat cards, quick actions, recent patients/encounters |
| Navigation | Role-gated links to Dashboard, Patients, and module-specific screens | ✅ Patients (gated), Pharmacy (gated), Reports (gated) |
| Patient Registration | National ID lookup *before* registering, to prevent duplicate records | ✅ Create/index/show/edit exist + National ID lookup box on create screen (`patients.search.national-id` API) |
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
- [x] National ID lookup on the create screen (wired via `patients.search.national-id` API — clerks search *before* registering)
- [x] Pediatric flow: guardian selection + child record creation (nullable national_id for children; select existing guardian or register new guardian inline; guardians seeded)

### Sprint 2 — QR Integration
**Purpose:** QR codes are what make return visits fast — without this, every visit degrades back to manual National ID lookup.
- [x] `QrCodeService` (SVG/data URL) + `showQrCode` + `getQrCode` API
- [x] QR scan/lookup entry point on `patients.index` (paste/scan DHP ID → open record)
- [x] Verify the QR prints correctly on the patient show page

### Sprint 3 — Triage
**Purpose:** Triage is the clinical safety layer — it's where abnormal vitals should push a patient up the queue before anything worse happens.
- [x] Triage GET form + `triage.save` POST (vitals + priority)
- [x] Vitals recorded with `patient_id`, `recorded_by_user_id`, `recorded_at`
- [x] Priority queue concept defined in the blueprint
- [x] Implement queue ordering on the dashboard/consultation list
- [x] Abnormal-vitals auto-prioritization using `Vital::isAbnormal()`

### Sprint 4 — Consultation
**Purpose:** This is the clinical decision-making core — where history, diagnosis, and treatment come together, and where the paper-based system currently fails most (Section 1.1 of your proposal cites fragmented records at referral/consultation as the core problem).
- [x] Consultation GET + `consultation.save` POST (complaint, findings, diagnosis, plan, admission flag)
- [x] Prescription creation *inside* consultation — currently only notes are saved; add `prescriptions[]` inputs to create real `Prescription` records
- [x] Wire encounter status transitions: triaged → consultation → completed

### Sprint 5 — Pharmacy & Inventory
**Purpose:** Closes the loop from prescription to medication in hand, and keeps stock counts trustworthy — a stated system objective (reduce duplicate tests/errors, improve medication safety).
- [x] Pharmacy screen lists encounter prescriptions + inventory
- [x] `pharmacy.dispense` sets `status=dispensed`, decrements stock
- [x] Inventory management UI (`manage_inventory` permission): add/edit/restock, low-stock flags
- [x] Only show dispense buttons for prescriptions still pending

### Sprint 6 — Inpatient (Admission / Ward / Discharge)
**Purpose:** Covers the "Inpatient Flow" branch of your blueprint's workflow — patients who need more than an OPD visit.
- [x] Admission form creates the encounter + admission record
- [x] Ward round records observations against the latest admission
- [x] Discharge captures final diagnosis, summary, follow-up
- [x] Ward medication administration log (blueprint §23)
- [x] Daily progress notes UI

### Sprint 7 — Synchronization
**Purpose:** This is what makes the system usable in the low-connectivity, low-resource environment your proposal specifically names as a constraint — hospitals must keep working when the internet doesn't.
- [x] `sync_queue` table, `SyncQueue` model, `sync.status`/`sync.upload` routes
- [x] `attemptSync()` simulated (95% success)
- [x] Wire sync enqueue into clinical write actions (patients, encounters, vitals, prescriptions, admissions)
- [x] Replace the inline simulation with a real background job (`queue:work` or scheduled command)
- [x] Sync status dashboard widget so staff can see whether they're offline

### Sprint 8 — Reporting
**Purpose:** This is what turns raw records into something the Hospital Administrator can act on, and it's the evidence layer your dissertation will draw on for evaluation.
- [x] Permissions exist: `view_reports`, `generate_reports`, `view_audit_logs`
- [x] Reports controller + views: patient census, OPD visits, admissions, dispensed meds, inventory
- [x] Role-gated access (admin / hospital_administrator only)

**Cross-cutting backlog (build alongside the sprints above, not after):**
- User Management (`manage_facility_users`) — no screen exists yet; needed before non-developers can manage staff accounts.
- Facility Management (`manage_facility`) — seed data exists, no UI.
- Audit logging on every clinical write action (blueprint §19) — required for accountability and for your ethics/data-security commitments.

**Done-when:** All `[ ]` items above are checked, and a fresh `migrate:fresh --seed` plus `php artisan test` both pass cleanly.

---

## Phase 4 — Cutover (Testing & Deployment)

**Why this phase exists:** RAD's construction speed only pays off if cutover is disciplined — this is where you prove the system actually works end-to-end, not just module by module, and where your proposal's usability/functionality targets (SUS ≥ 70, 95% pass rate) get measured.

### Testing
- [x] Feature tests for each new module (users, facilities, inventory, prescriptions, reports)
- [x] `php artisan test` — all green
- [x] `migrate:fresh --seed` on a clean database, then verify every route responds
- [x] Browser smoke test: log in as each of the 9 seeded users and perform a full OPD + inpatient flow
- [ ] Usability testing with ≥5 healthcare workers, targeting SUS ≥ 70 and a 95% functionality pass rate (per your proposal's Objective d)

### Deployment
- [ ] `npm run build` — confirm `public/build/manifest.json` and assets exist
- [ ] `config:cache`, `route:cache`, `view:cache`
- [ ] `APP_ENV=production`, `APP_DEBUG=false` in `.env`
- [ ] HTTPS/TLS on the hosting server
- [ ] Pilot at Ndirande Health Centre, then plan for wider rollout

**Done-when:** The pilot facility can run a real day of OPD + inpatient activity on the system without a developer in the room, and the usability results are documented for your write-up.

---

## To fully function system remaining work

This section identifies the remaining work required to turn the current prototype into a fully functional digital health passport system for Ndirande Community Health Centre and similar Malawi health facilities.

### 1) Complete the administrative backbone
- Build user management screen for hospital staff.
- Allow admin to create, edit, deactivate, and assign roles to users.
- Add facility management screen to manage health facility details.
- Connect facility and user assignments to the patient flow and permissions model.

### 2) Implement audit logging for all clinical actions
- Create an audit log table and model.
- Log every relevant patient, triage, consultation, prescription, pharmacy, admission, and discharge event.
- Capture who changed the record, when it happened, what action was taken, and the record affected.
- Provide an administrator view for audit history and accountability.

### 3) Finalize missing clinical workflows
- Add a formal lab and diagnostic request/result workflow.
- Link diagnostics to the consultation encounter and patient record.
- Ensure clinician review of lab results can update diagnosis or treatment.
- Confirm patient timeline includes all diagnostic updates and follow-up actions.

### 4) Improve data consistency and workflow integrity
- Validate and fix patient encounter status transitions across registration → triage → consultation → discharge.
- Prevent duplicate active admissions for the same patient.
- Guarantee only pending prescriptions can be dispensed.
- Review ward medication administration and discharge summary workflow for consistency.

### 5) Strengthen the user interface for efficiency
- Reduce repeated clicks in the patient journey.
- Improve dashboard visibility for queues, waiting patients, and pending tasks.
- Add fast patient lookup and direct navigation from triage to consultation to pharmacy.
- Make the same patient record easy to access across departments without re-entering information.

### 6) Complete production readiness
- Run `npm run build` and confirm production assets are generated.
- Run `config:cache`, `route:cache`, and `view:cache`.
- Set `APP_ENV=production` and `APP_DEBUG=false` before deployment.
- Configure HTTPS/TLS on the hosting environment.
- Test the system in a live pilot environment.

### 7) Validate usability and functional performance
- Conduct usability testing with at least 5 healthcare workers.
- Measure SUS score and functionality pass rate.
- Collect feedback on queue flow, patient tracking, and staff workload.
- Refine the interface based on local operational realities.

### 8) Final handover and pilot deployment
- Pilot at Ndirande Community Health Centre or similar low-resource setting.
- Train staff on the patient journey and role-based usage.
- Monitor workflow efficiency before broader rollout.
- Fix issues discovered during the pilot before final sign-off.

### Priority order to finish the project
1. User management and facility management
2. Audit logging
3. Workflow consistency and data integrity fixes
4. Lab/diagnostic module integration
5. Interface efficiency improvements
6. Production deployment setup
7. Pilot testing and final validation

**Done-when:** The system can support a full patient journey from registration to discharge with secure role-based access, clear accountability, operational efficiency, and a pilot-ready production setup.

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
| (e) Centralized digital health passport with shared patient data, distinct from EMR/EHR systems, and improved interface efficiency | Phase 1 → Phase 2 → Phase 3 |

\* Immunization tracking and lab results aren't yet explicit modules in the current blueprint/sprint list — worth flagging with your supervisor if they're required deliverables, since the current scope (Section 5 of the blueprint) explicitly excludes laboratory systems.



## website flow 
\* The Paper Health Passport: A Patient Journey in Malawi
In Malawi, the health passport (or kabuku ka za umoyo) is more than just a notebook; it is a lifelong, portable medical record. The following steps detail a standard journey through a public hospital or health center, from arrival to discharge.

Phase 1: Reception and Triage
1. Registration and Vital Signs
Upon arriving at the facility, your first stop is the registration or triage desk. You must present your health passport to the clerk or nurse. Without it, you are typically not permitted to proceed. The nurse records your presenting complaint and measures your baseline vital signs—weight, temperature, blood pressure, and heart rate—writing these figures directly into a new row in the passport.

2. Queue Allocation
Based on the urgency of your symptoms and the vitals just recorded, the triage nurse uses the book to determine your place in line. The passport acts as your ticket; it is often stacked in a pile or tray to maintain the order of patients waiting to see a clinician.

Phase 2: The Clinical Encounter
3. History Review
When your name is called, you move to the consultation room and hand the passport to the clinician (a Clinical Officer, Medical Assistant, or Doctor). The clinician immediately flips through the previous pages. This is a critical moment where they look for chronic conditions, drug allergies, or recent treatments that might inform today's diagnosis.

4. Diagnosis and Documentation
The clinician examines you and makes a diagnosis. They then write the details of your current visit in the passport, including:

Presenting symptoms (e.g., fever, cough, pain).

Physical examination findings.

The final clinical diagnosis.

5. Laboratory and Diagnostic Requests
If further investigation is needed (such as a malaria rapid test, sputum test, or X-ray), the clinician writes the specific test request directly on the page of the passport.

6. The Lab Round-Trip
You take the passport to the laboratory or radiology department. The technician performs the requested test, writes the numerical or qualitative results (e.g., "Malaria Parasites: Positive +++") directly into the same book, and sends you back to the clinician with the results in hand. The clinician reviews the results to confirm or adjust the diagnosis.

Phase 3: Divergence of Care
At this point, the clinician decides if you are stable enough to go home or if you require hospital supervision.

Path A: Outpatient (Home Care)

7. Outpatient Prescription
If your condition is manageable at home, the clinician writes the prescription (medication, dosage, frequency, and duration) directly onto the current page of the passport.

8. Pharmacy Fulfillment
You proceed to the hospital pharmacy and hand the passport through the window or over the counter. The dispenser reads the prescription, retrieves the medication, and dispenses it to you. To prevent double-dispensing or fraud, the dispenser stamps the page or ticks the prescription. You take both your medicine and your passport home.

Path B: Inpatient (Admission)

7. Admission Orders
If your condition requires monitoring, surgery, or intensive treatment, the clinician writes an admission order in the passport. This note specifies the reason for admission and the ward to which you are assigned (e.g., Female Surgical, Paediatric, or Maternity Ward).

8. Ward Handover and Bedside Record
You walk to the designated ward and hand the passport to the nurse in charge. From this moment, the passport ceases to be your personal item and becomes a legal medical document. It remains at the nurse's station or is kept in a folder at the foot of your bed.

9. Continuous Bedside Tracking
During your stay, the passport functions as the central chart. Nurses record vital signs, fluid intake/output, and injected medications. When doctors do their rounds, they write daily progress notes, changes in treatment plans, and surgical notes directly into the booklet. If the booklet runs out of space, supplementary paper charts are folded and stapled inside.

10. Discharge Summary
When you are deemed fit to leave, the doctor writes a comprehensive discharge summary on a fresh page of the passport. This summary includes:

Final diagnosis.

Key treatments received during the stay.

Results of major investigations.

Instructions for follow-up appointments.

Take-home medications.

The passport is then physically handed back to you. You are responsible for carrying this updated record home and bringing it back for any future medical visits.