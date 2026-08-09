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
