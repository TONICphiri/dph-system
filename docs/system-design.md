# Digital Health Passport — System Design

## 1. Problem statement and Malawi context

Citizens carry a paper health passport: a booklet or card with stamped
vaccination records and laboratory information. Paper gets lost, damaged,
forged, fragmented and forgotten. This prototype digitizes selected paper
health-passport entries as independently verifiable health credentials for
Malawi. Each credential can be looked up by authorized staff, presented by
QR code (phone or printed certificate), verified by a third party, and
corrected, expired, revoked or replaced when necessary.

National ID is an identifier, not a password, PIN source, QR payload, or
verification token. Citizens without smartphones are served through
health-worker assisted access at participating facilities.

## 2. Scope and exclusions

In scope: citizen registration, assisted identity confirmation, vaccination
and laboratory-test credentials, QR presentation and printing,
public/authenticated verification, correction, revocation, replacement,
expiry, notifications, encrypted backup/restore, administration and audit.

Excluded (not a hospital management system, not a full medical record):

```text
Appointments, queues, triage, clinical notes, diagnoses, prescriptions, pharmacy, inventory, billing, insurance, admissions, beds, wards, theatre, staff scheduling, and full medical history.
```

## 3. Roles

| Role | Capabilities |
|---|---|
| Citizen (smartphone) | Own passport, active QR display, own certificate printing |
| Issuer (health worker) | Citizen search/registration, identity confirmation, issue/correct/revoke/replace, printing |
| Verifier | QR scan or credential-number check, minimum-disclosure result |
| Administrator | DHP users, facilities, audit review, backup operations |

## 4. Core workflow

```text
Citizen registration
→ Identity confirmation
→ Credential issuance
→ Citizen QR/printed presentation
→ Verification
→ Correction/revocation/replacement/expiry
→ Audit
```

Smartphone flow: citizen signs in → My Passport → views credential → shows
QR or prints certificate → presents to verifier.
Non-smartphone flow: citizen presents National ID/Passport ID at a facility
→ issuer searches → confirms two demographics → issues/updates credentials
→ prints QR certificate, which acts as the paper proof.

## 5. Architecture

```text
Citizen portal ─┐
Issuer portal ──┼── Laravel 11 (Blade + Tailwind + vanilla JS)
Verifier / public verification ─┘        │
Admin portal ────────────────────────────┤
                                         ├── MySQL
Queue worker (database queue) ───────────┤
Mailtrap / default mailer ───────────────┤ (notifications)
Backup mailer ───────────────────────────┤ (encrypted archives)
Encrypted non-public backup storage ─────┘ (storage/app/backups)
```

Components: citizen portal (read-only), issuer portal (assisted access),
public verification + authenticated verifier portal, admin portal (users,
facilities, audit, backups), Laravel backend (policies, middleware,
services, scheduler), MySQL, queue worker, two mailers, encrypted storage.
QR scanning uses the locally bundled `html5-qrcode` library, loaded only on
verification pages.

## 6. Data model summary

- `users`: DHP role (`citizen`, `issuer`, `verifier`, `admin`), active flag.
- `citizens`: passport ID (`MW-DHP-YYYY-XXXXXX`), optional National ID,
  demographics, optional linked login, nullable PIN hash.
- `credentials`: credential number (`MW-CRED-YYYY-XXXXXX`), type
  (`vaccination`, `lab_test`), status, dates, opaque 64-char QR token,
  revocation and replacement links.
- `vaccination_details` / `test_details`: per-type clinical data, kept out
  of QR payloads, verifier output, emails and audit metadata.
- `verifications`: every check recorded (method, result, verifier, IP).
- `audit_logs`: action, entity, JSON details (sensitive values scrubbed).
- `notification_deliveries`: idempotent notification tracking.
- `backup_runs`: safe backup history (no filenames, paths or secrets).

## 7. Security and privacy controls

- `users.role` + `is_active` is the single DHP authorization source;
  `CitizenPolicy`/`CredentialPolicy` enforce ownership; denials return 403.
- National ID is never a secret, QR content, or log value; masked in UI.
- QR tokens are random and opaque; QR encodes only a verification URL.
- Verifier sees minimal data: masked name/Passport ID when public, full
  name/Passport ID only for authenticated verifiers on valid credentials.
  Never: National ID, DOB, contacts, location, clinical detail, tokens.
- Rate limits on login, PIN, suggestions and public verification.
- Audit scrubbing blocks passwords, PINs, National ID, DOB, results,
  QR tokens and session/reset tokens.
- Backups are AES-256 encrypted, non-public, never downloadable by web,
  emailed only as encrypted attachments under a size limit.

## 8. Credential status lifecycle

```text
active → expired (date reached or nightly command)
active → revoked (revoke-only or suspected fraud replacement)
active → superseded → replaced_by_credential_id (administrative replacement)
```

Effective status reports `expired` whenever the expiry date is past, so
verification stays correct even before the scheduler runs. Old QR tokens
never validate as active after revocation/replacement.

## 9. Backup/restore architecture

Daily encrypted database-only backup (`backup:run-and-email`, 02:00),
cleanup (02:30, daily-14/weekly-8/monthly-6), monitor (03:00). Optional
emailed encrypted copy via a dedicated mailer with size guard. Admin page
shows metadata only; manual runs go through a queued job with overlap lock.
Restore is CLI-only (`backup:restore {file} --force`), password-gated,
production-gated, confirmation-gated, extracts to a temp directory that is
always removed, and imports via escaped `mysql` invocation. Restore during
planned maintenance only (`php artisan down`, pause workers/scheduler).

## 10. Testing strategy

Feature suites per phase (data model, access control, issuer flow, citizen
portal, verification privacy, administration/expiry, notifications, backup/
restore, UI/accessibility/scope) plus legacy regression suites. Run with
`php artisan test` against an isolated MySQL test database. Key properties
tested: ownership isolation, minimum disclosure, opaque QR tokens, idempotent
commands, encrypted restorable backups, and no sensitive data in emails,
QR payloads, screens, JS responses, audits or logs.

## 11. Future work and limitations

No National Registration Bureau / MaHIS / DHIS2 integration, no SMS/USSD or
offline app, verification needs connectivity, assisted access remains
essential, email-gated notifications skip no-email citizens without blocking
care, backup/restore needs server tooling and authorized operators. National
deployment would require Ministry of Health governance, legal review,
hardening, key management and interoperability standards.
