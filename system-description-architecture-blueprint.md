# System Description, Architecture, and RAD-Based Development Blueprint

## Prepared for: Malawi University of Business and Applied Sciences (MUBAS)

## Project Type: Web-Based Digital Health Passport System

## Development Methodology: Rapid Application Development (RAD)

## Technology Stack: HTML5, CSS3, JavaScript, PHP, Python, MySQL

---

## Table of Contents

1. Executive Summary
2. Background and Problem Statement
3. Proposed Solution
4. System Objectives
5. Scope of the System
6. Users and Roles
7. Overall System Workflow
8. National Patient Identification Model
9. Pediatric (Child) Registration Model
10. System Architecture
11. RAD Development Architecture
12. Functional Requirements
13. Non-Functional Requirements
14. Database Architecture
15. Application Architecture
16. User Interface Architecture
17. API Architecture
18. Offline and Synchronization Architecture
19. Security Architecture
20. QR Code Architecture
21. Clinical Workflow Architecture
22. Pharmacy and Inventory Workflow
23. Inpatient Workflow
24. Deployment Architecture
25. Development Phases (RAD)
26. Suggested Folder Structure
27. Future Expansion
28. Conclusion

---

## 1. Executive Summary

The Nationwide Digital Health Passport System is a web-based healthcare information system designed for hospitals and health facilities across Malawi. The system replaces paper-based health passports with a centralized digital patient record that can be accessed from any registered hospital in the country.

Patients are identified primarily using their National ID number. During their first hospital visit, a digital health passport profile is created and a QR code is generated for faster identification during future visits.

The system supports outpatient (OPD) services, inpatient admissions, pharmacy dispensing, ward rounds, and longitudinal medical history tracking.

The application is optimized for low-resource environments where hospitals may have older desktop computers, budget Android smartphones, and unstable internet connectivity.

## 2. Background and Problem Statement

Malawi currently relies heavily on paper health passports. These paper booklets can be:
- Lost
- Damaged
- Forgotten at home
- Difficult to read
- Impossible to access from another hospital

A patient treated in Blantyre cannot easily provide their previous treatment history when visiting a hospital in Lilongwe or Mzuzu.

This leads to:
- Duplicate patient records
- Repeated laboratory tests
- Incorrect medication histories
- Delayed treatment
- Poor continuity of care

The proposed system creates one lifelong electronic health passport for every patient.

## 3. Proposed Solution

A centralized national electronic health passport platform.

Every patient has:
- National ID number
- Digital Health Passport ID
- QR code
- Lifelong medical timeline

Hospitals connect to a central national database while maintaining local cached copies for offline operation.

## 4. System Objectives

The system will:
- Register patients digitally
- Identify patients using National ID numbers
- Generate QR codes
- Store lifelong medical histories
- Support OPD and inpatient workflows
- Allow cross-hospital record retrieval
- Reduce duplicate registrations
- Improve medication safety
- Support offline operation
- Synchronize records nationally

## 5. Scope of the System

### Included:
- Registration
- OPD
- Triage
- Consultation
- Pharmacy
- Inpatient wards
- Discharge
- QR identification
- National ID identification
- National synchronization

### Excluded:
- Laboratory systems
- Radiology systems
- Billing systems
- Insurance processing

## 6. Users and Roles

### Registration Clerk
**Responsibilities:**
- Search by National ID
- Register new patients
- Generate QR codes
- Check patients into OPD

### Triage Nurse
**Responsibilities:**
- Record vital signs
- Prioritize patients
- Move patients into consultation queue

### Clinical Officer / Doctor
**Responsibilities:**
- Review history
- Record consultation notes
- Diagnose
- Prescribe medication
- Admit patients
- Discharge patients

### Pharmacist
**Responsibilities:**
- View prescriptions
- Dispense medication
- Update stock

### Ward Nurse
**Responsibilities:**
- Record ward observations
- Administer medication
- Update inpatient charts

### Hospital Administrator
**Responsibilities:**
- Manage users
- Manage facilities
- Audit logs
- System reports

## 7. Overall System Workflow

### OUTPATIENT FLOW
Registration → Triage → Consultation → Pharmacy → Exit

### INPATIENT FLOW
Registration → Triage → Consultation → Admission → Ward → Discharge

## 8. National Patient Identification Model

### Primary Identifier
National ID Number
Example: A123456789

### Registration Process

**First Visit:**
1. Clerk enters National ID
2. System searches database
3. If found → open patient record
4. If not found → create new patient
5. Generate Digital Health Passport ID
6. Generate QR code

**Future Visit:**
- If patient has QR code: Scan QR → Open record instantly
- If patient does not have QR: Enter National ID manually

## 9. Pediatric (Child) Registration Model

Children without National IDs are not stored inside the mother's medical record. Instead:
- A separate child patient record is created immediately
- The child is linked to the mother as the primary guardian

```
Mother Record
↓
Linked Children
↓
Child Record
```

When the child later receives a National ID, the existing child record is updated. No medical history is transferred because the child has always had an independent record.

## 10. System Architecture

The system uses a four-layer architecture:

### Client Layer
- Desktop browsers: Registration, Consultation, Pharmacy, Administration
- Android smartphones: Triage, Ward rounds, QR scanning

### Application Layer
**PHP Responsibilities:**
- Authentication
- Routing
- Business logic
- CRUD operations

**Python Responsibilities:**
- Malawi Standard Treatment Guideline (MSTG) engine
- Clinical decision support
- Reporting

### Database Layer
MySQL stores:
- Patients
- Encounters
- Vitals
- Prescriptions
- Admissions
- Users
- Facilities
- Sync queue

## 11. RAD Development Architecture

The system follows Rapid Application Development.

### RAD Phase 1: Requirements Planning
**Outputs:**
- Workflow diagrams
- User roles
- Database draft
- Screen sketches

### RAD Phase 2: User Design
**Prototype:**
- Registration page
- Patient lookup
- Triage screen
- Consultation dashboard

Feedback is collected from clinicians and nurses.

### RAD Phase 3: Construction
**Sprint 1:** Registration
**Sprint 2:** QR generation
**Sprint 3:** Triage
**Sprint 4:** Consultation
**Sprint 5:** Pharmacy
**Sprint 6:** Inpatient
**Sprint 7:** Synchronization
**Sprint 8:** Reporting

### RAD Phase 4: Cutover
**Activities:**
- Testing
- User training
- Data migration
- Pilot deployment
- National rollout

## 12. Functional Requirements

### Patient Registration
- Register patient
- Search by National ID
- Generate QR
- Edit demographics

### Patient Lookup
- National ID search
- QR search
- Guardian lookup

### Triage
- Weight
- Temperature
- Blood pressure
- Priority classification

### Consultation
- Symptoms
- Examination
- Diagnosis
- Treatment
- Admission decision

### Pharmacy
- Receive prescription
- Dispense medication
- Update inventory

### Ward
- Bed assignment
- Medication administration
- Progress notes

### Discharge
- Final diagnosis
- Summary
- Follow-up instructions

## 13. Non-Functional Requirements

### Performance
Patient lookup: Less than 2 seconds

### Reliability
Offline operation supported

### Availability
99% target

### Security
Encrypted communication

### Scalability
Support all Malawian hospitals

## 14. Database Architecture

### Core Tables
- **patients** - Permanent identity
- **facilities** - Hospital registry
- **users** - Staff accounts
- **guardians** - Child-parent relationship
- **encounters** - Hospital visit
- **vitals** - Clinical measurements
- **prescriptions** - Medication orders
- **clinical_notes** - Compressed notes
- **admissions** - Inpatient records
- **inventory** - Pharmacy stock
- **sync_queue** - Offline synchronization

## 15. Application Architecture

### Presentation Layer
- HTML5
- CSS3
- JavaScript

### Business Layer
- PHP Controllers: PatientController, EncounterController, PharmacyController
- Python Services: MSTG Service, Reporting Service

### Data Layer
- MySQL

## 16. User Interface Architecture

### Desktop
- Left Panel: Patient history timeline
- Right Panel: Current consultation form

### Smartphone
- Single-column layout
- Large buttons
- Offline support
- Camera QR scanner

## 17. API Architecture

### Examples
- GET /api/patient/search?national_id=
- POST /api/patient/register
- POST /api/triage/save
- POST /api/consultation/save
- POST /api/pharmacy/dispense
- POST /api/admission/create
- POST /api/sync/upload

### Responses
- JSON format

## 18. Offline and Synchronization Architecture

Every hospital has:
- Local MySQL
- Local cache
- Sync queue

### Workflow
**Internet available:**
1. Write locally
2. Upload immediately

**Internet unavailable:**
1. Write locally
2. Add to sync_queue
3. Background sync
4. National database

**Python Sync Service:**
- Runs every 60 seconds
- Retries failed uploads
- Marks successful records as synced

## 19. Security Architecture

### Authentication
- Username
- Password
- Role permissions

### Communication
- HTTPS
- TLS encryption

### Audit Logging
Every action records:
- User
- Time
- Facility
- Device

## 20. QR Code Architecture

### QR Contains
- Digital Health Passport ID
- Example: DHP-2026-00001234

### Scanning Process
- Camera
- JavaScript QR scanner
- PHP API
- Patient record

## 21. Clinical Workflow Architecture

### Registration → Triage → Consultation → Decision → Discharge OR Admission

### Priority Queue
- Abnormal vitals automatically move patients higher in the clinician queue

## 22. Pharmacy and Inventory Workflow

### Prescription → Pharmacy Queue → QR Scan → Dispense → Inventory Reduction
Stock levels update automatically.

## 23. Inpatient Workflow

### Admission → Ward Assignment → Medication Administration → Daily Notes → Discharge
Ward nurses use smartphones during rounds.

## 24. Deployment Architecture

### National Cloud Server
- Central MySQL
- API
- Backup

### Hospital Server
- Local MySQL
- PHP
- Python
- Sync service

### Client Devices
- Desktop browsers
- Android smartphones

## 25. Development Phases (RAD)

### Phase 1: Registration Prototype
### Phase 2: Patient Lookup
### Phase 3: QR Integration
### Phase 4: Triage
### Phase 5: Consultation
### Phase 6: Pharmacy
### Phase 7: Admission
### Phase 8: Ward
### Phase 9: Synchronization
### Phase 10: Testing and Deployment

## 26. Suggested Folder Structure

```
/public
  /css
  /js
  /images
  /api
    /patient/
    /encounter/
    /pharmacy/

/app
  /controllers/
  /models/
  /services/

/python
  mstg_engine.py
  sync_service.py

/database
  schema.sql
  seed.sql

/config
  database.php
  auth.php

/routes
  web.php
  api.php
```

## 27. Future Expansion

- Laboratory integration
- Radiology integration
- Appointment booking
- SMS reminders
- Maternal health tracking
- Vaccination tracking
- National disease surveillance
- DHIS2 integration
- OpenMRS interoperability
- Biometric identification

## 28. Conclusion

This system creates a single lifelong electronic health passport for every patient in Malawi. Patients are identified primarily through their National ID number, while QR codes provide rapid access for registered users. Children without National IDs receive independent patient records linked to their mother or guardian, ensuring continuity of care from birth through adulthood. A centralized national database with local hospital caching enables cross-hospital access while maintaining functionality during internet outages. The Rapid Application Development methodology allows the system to be built incrementally through working prototypes, making it practical for real hospital environments and suitable for implementation as a MUBAS capstone project.

---

## Appendix: Build Documentation

### Build Configuration

#### npm Scripts
- `npm run build` - Production build using Vite
- `npm run dev` - Development server with hot reloading

#### Vite Configuration (`vite.config.js`)
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

#### Build Output (`public/build/assets/`)
- `app-BPjt-bD7.js` - 94KB (Compiled JavaScript bundle)
- `app-CEwZte8_.css` - 35KB (Compiled CSS bundle with Tailwind)

#### Manifest
- `public/build/manifest.json` - Build metadata and asset mapping

### System Overview

This Laravel application uses:
- **Vite** (v7.3.6) - Asset bundling and dev server
- **Tailwind CSS** (v3.1.0) - Utility-first CSS framework
- **Alpine.js** (v3.4.2) - Reactive JavaScript framework
- **Bootstrap** (v5.3.8) - UI component library

### Recent Cleanup Changes

- **Blade Component Renaming:**
  - `update-profile-information-form.blade.php` → `profile-form.blade.php`
  - `update-password-form.blade.php` → `password-form.blade.php`
  - Updated references in `resources/views/profile/edit.blade.php`

- **Error Pages Cleanup:**
  - Verified `resources/views/errors/` contains only customized pages: 404, 419, 429, 500

- **Directory Structure:**
  - Kept `app/Repositories/` and `app/Actions/` (empty, confirmed needed)

### Build Process

```bash
# Development mode
npm run dev

# Production build  
npm run build
```

### Post-Build Verification
1. Check `public/build/assets/` for compiled assets
2. Verify `public/build/manifest.json` exists
3. Ensure all Blade templates reference correct component paths
4. Test application functionality
```