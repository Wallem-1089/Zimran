# Database Test Safety

Automated database tests must never use the application database.

## Required environment

Configure through the CLI or CI environment, never browser input:

```text
HMS_APP_ENV=testing
HMS_TEST_DB_NAME=hms_test_hospital_management_system
HMS_VERIFIED_BACKUP_PATH=<absolute verified SQL backup path>
```

The test name must match `hms_test_[a-z0-9_]+` and differ from live.

## Rebuild and test

Run:

```text
php database/tools/rebuild_test_database.php --confirm-destructive-test-db
```

The tool prints both names, verifies the backup gate, rejects unsafe schema SQL,
and logs target/reason/authorization to
`storage/logs/database_operations.log`. `database/schema.sql` is neutral.
Never use `hospital.sql` as an automated import source.

All database tests include `config/test_database.php`. The normal bootstrap
throws in testing mode. Run `php tests/database_safety_test.php` to verify the
refusal rules before the regression suites.

## Fixtures and backups

`database/fixtures/development_seed.php` is CLI-only,
development/testing-only, idempotent, and requires explicit enablement plus a
protected password of at least 12 characters. It embeds no credentials and
creates only labelled `DEV-*` records.

Create and verify a backup before every migration and another afterward. A
backup must be readable, non-empty, and recognized as a MySQL/MariaDB dump.
Otherwise destructive work stops.

## Phase 3.4 Laboratory CRUD

Use only the explicit dedicated database configuration:

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase3_laboratory_test.php
```

The focused suite covers clinical and direct laboratory requests, result
entry, completion, permissions, encounter locking, audit/event hooks,
consultation and workspace integration, and regression checks for the major
encounter flow.

## Phase 2.4 Problem List and Medical History

Use only the explicit dedicated database configuration:

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase2_problem_list_medical_history_test.php
```

The focused suite covers problem/history creation, validation, lifecycle,
verification separation, material-edit reset, stale updates, duplicate active
problems, confidentiality masking, optional encounter context, immutable
versions, audit/event atomicity, and rollback on audit failure.

Migration 019 down/up verification is destructive and may run only against an
empty dedicated test database after the current-session backup gate succeeds:

```powershell
$env:HMS_VERIFIED_BACKUP_PATH='C:\verified\current-backup.sql'
php test\phase2_migration019_cycle_test.php --confirm-destructive-test-db
```

The cycle test prints resolved live/test names, refuses identical or ambiguous
targets, preflights SQL, checks empty Milestone 2.4 tables, validates restored
tables and confirms the Migration 019 checksum. It never targets the live
application database.

## Phase 2.5 Secure Medical Documents

The focused suite uses the dedicated test database and an isolated storage root
under `storage/tests/`; it removes all file fixtures after execution.

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase2_medical_documents_test.php
```

Coverage includes PDF/JPEG/PNG validation, MIME spoofing, executable/double
extension/traversal rejection, size and missing-file errors, opaque storage,
checksums, quarantine, patient/encounter linkage, immutable replacement,
archival/restoration/entered-in-error, confidentiality, downloads, historical
versions, closed encounters, access-log failure, rollback, event/audit/storage
failures, orphan compensation, and cleanup.

Migration 020 down/up verification is destructive and requires the verified
current-session backup gate plus explicit dedicated-test approval:

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
$env:HMS_VERIFIED_BACKUP_PATH='C:\verified\current-backup.sql'
php test\phase2_migration020_cycle_test.php --confirm-destructive-test-db
```

Never point `HMS_DOCUMENT_STORAGE_ROOT` at live storage during tests. Refuse to
run unless `config/test_database.php` resolves a distinct approved test DB.

## Phase 2.6 Clinical Notes

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase2_clinical_notes_test.php
```

The suite covers plain-text validation, draft ownership, immutable versions,
stale updates, administrator non-signing, Doctor signing/locking, independent
amendment approval, entered-in-error, confidentiality, PHI access,
visit/patient validation, audit/event atomicity, and cleanup.

Migration 021 rollback/reapply is destructive and requires a current-session
backup and explicit test-database confirmation:

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
$env:HMS_VERIFIED_BACKUP_PATH='C:\verified\current-backup.sql'
php test\phase2_migration021_cycle_test.php --confirm-destructive-test-db
```

## Phase 3.1 Consultation and Department Notifications

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase3_consultation_notifications_test.php
```

The focused suite covers universal patient-search access, active encounter
discovery, Doctor and Administrator consultation CRUD, clinical doctor
attribution, unauthorized mutation denial, completed/cancelled encounter
read-only enforcement, consultation review/save persistence, department
notification send/read/resolve, inbox visibility, audit, encounter events for
start/complete/sent only, and confirmation that notifications do not transfer
encounters or alter queue ownership.

## Phase 3.2 Vital Signs

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase3_vital_signs_test.php
```

The focused suite verifies doctor, nurse and administrator create/update/view
paths; unauthorized mutation denial; multiple records per visit; latest-record
retrieval; visit and patient history reads; patient/visit mismatch rejection;
completed/cancelled read-only enforcement; BMI calculation; invalid range
rejection; CSRF-aware controller wiring; audit generation; and the integration
hooks used by the Workspace, Consultation page and Patient Chart. Existing
major workflows remain covered by the previous regression suites.

## Phase 3.3 Nursing

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase3_nursing_test.php
```

The focused suite verifies nurse, doctor and administrator access; create,
update and complete flows; one-primary-assessment-per-visit enforcement;
patient/visit mismatch rejection; completed/cancelled read-only behavior;
Vital Signs, Clinical Safety, Problem List, and Medical History integration;
patient-chart/workspace wiring; audit and encounter-event generation; and
existing Consultation/Vital Signs regression coverage.

## Phase 3.5 Radiology

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase3_radiology_test.php
```

The focused suite verifies clinical and direct radiology requests; Radiographer
and Doctor access; unauthorized mutation denial; patient/visit mismatch
rejection; completed/cancelled read-only enforcement; report creation and
update; completion without report rejection; CSRF-aware controller wiring;
audit generation; encounter-event generation; and the integration hooks used
by the Workspace, Consultation page and Patient Chart. Existing Consultation,
Vital Signs, Nursing, Laboratory, Workspace, and Department Notification
regression coverage remains in place.

## Phase 3.6 Physiotherapy

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase3_physiotherapy_test.php
```

The focused suite verifies clinical and direct physiotherapy records;
Physiotherapist, Doctor, Nurse, and Administrator access; unauthorized
mutation denial; patient/visit mismatch rejection; session create/update;
completion and read-only locking; CSRF-aware controller wiring; audit
generation; encounter-event generation; and the workspace, consultation, and
patient-chart integration hooks. Existing Consultation, Vital Signs, Nursing,
Laboratory, Radiology, Workspace, and Department Notification regression
coverage remains in place.

## Phase 3.7 Theatre

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase3_theatre_test.php
```

The focused suite verifies Theatre staff, Doctor, Nurse, and Administrator
create/update/view/complete flows; clinical attribution preservation;
unauthorized mutation denial; duplicate record prevention; patient/visit
mismatch rejection; completed/cancelled read-only enforcement; CSRF-aware
controller wiring; audit generation; encounter-event generation; workspace
integration; department notification integration; and the existing
Consultation, Vital Signs, Nursing, Physiotherapy, Laboratory, Radiology, and
chart regression coverage.

## Phase 4.1 Accounts / Price Catalogue

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase4_accounts_test.php
```

The focused suite verifies service and product creation, duplicate code
rejection, edit/price update, activate/deactivate, role-based permissions,
administrator override, unauthorized mutation denial, search/filter behavior,
CSRF-aware controller wiring, audit generation, and sidebar visibility. It
also confirms that no Accounts Encounter Workspace tab was introduced and
that the module remains independent from patient charges, invoices, payments,
and receipts.

## Phase 4.2 Store / Inventory

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase4_store_test.php
```

The focused suite verifies inventory item CRUD, optional billable-item
linkage, receive/issue/return/adjust stock transactions, department balances,
ledger history, item activation toggles, role-based permissions,
administrator override, unauthorized mutation denial, CSRF-aware controller
wiring, audit generation, and sidebar visibility. It also confirms that no
Store Encounter Workspace tab was introduced and that the module remains
independent from Pharmacy dispensing and Billing.

Migration 043 adds External Store Sales. Practical verification should cover:
sale creation from Store stock with an active Accounts price link, price
snapshot behavior, Store stock reduction through the stock ledger, receipt
view/print page, cancellation with reason, Store Officer permissions,
Accountant view/receipt access, unauthorized mutation denial, CSRF, and audit.
External sales must not create patients, encounters, patient charges, invoices,
Billing payments, or Pharmacy dispensing records.

## Phase 4.3 Pharmacy

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase4_pharmacy_test.php
```

The focused suite verifies clinical prescriptions, direct Pharmacy
prescriptions without Consultation, dispensing through existing Store stock,
sufficient/insufficient stock behavior, duplicate dispensing rejection,
cancelled-prescription protection, Accounts price read-only display,
authorization, CSRF-aware controller wiring, audit/events, and Store stock
integrity regression.

## Phase 4.4 Billing / Patient Accounts

```powershell
$env:HMS_APP_ENV='testing'
$env:HMS_TEST_DB_NAME='hms_test_hospital_management_system'
php test\phase4_billing_test.php
```

The focused suite verifies manual charges from active billable items, price
snapshot behavior, multiple charges, invoice creation/refresh, partial and full
payments, invoice status transitions, receipt data, duplicate source-charge
prevention, over-balance payment rejection, authorization, CSRF-aware
controller wiring, audit, and directly affected Accounts/Store/Pharmacy and
Workspace regressions.

Billing Request testing should verify that department users can create
non-financial requests, Accounts/Admin can list pending requests, convert one
request into exactly one patient charge using `billable_items`, cancel pending
requests, and that pending/cancelled requests do not change invoice totals or
patient balances.

## Phase 4.5 Basic Dashboards / Reports

Run `php test/phase4_reports_test.php` against the dedicated test database.
The test applies the required Phase 4 migrations through the migration
manager, verifies report permissions, administrator dashboard contract,
report aggregate contracts, sidebar visibility, and guards against narrative
PHI leakage in the basic report pages.

## Inpatient Admissions / Ward & Bed Workflow

Focused tests should verify ward and bed creation, admission to an active
encounter, duplicate admission prevention, occupied-bed rejection, ward/bed
transfer, discharge bed release, cancellation bed release,
completed/cancelled-encounter mutation rejection, permissions, CSRF, audit, and
encounter timeline events.

## Current UI/route audit expectations

Repo-wide PHP syntax validation currently passes. Known non-blocking cleanup
targets should be tracked separately from feature tests:

- `authentication/forgot_password.php` is not implemented; administrator
  password reset remains the supported recovery path.
- legacy zero-byte role dashboard files, legacy `includes/`, and old
  `workspace/` helper files should not be treated as current routes.
- the active Encounter Workspace route is `modules/visits/workspace.php`.
- invalid-request fallback redirects should target real pages; known cleanup
  candidates include Radiology validation failure and Visit edit/update invalid
  fallback routes.
- Consultation handwriting mode should be manually smoke-tested in create/edit,
  review, and view pages because it is a browser pointer/canvas interaction.

## Migration 038 Clinical Cross-View Permissions

When testing clinical module access, verify both sides of the current policy:

- Doctor, Nurse, Laboratory Scientist, Radiographer/X-Ray, Physiotherapist,
  Theatre Staff, Pharmacist, and Records Officer can view the core clinical
  encounter context where patient/encounter scope permits it.
- Create/edit/complete/process/dispense permissions remain department-owned.
- Vital Signs create/edit remains limited to Doctor, Nurse, and Administrator.

Migration 038 was applied live with guarded backup flow on 2026-08-23.
