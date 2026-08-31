# Enterprise Hospital Management Information System (E-HMIS)

> **Read this file before making any code changes.**
>
> This document defines the architecture, coding standards, workflow, project goals, and development roadmap for this repository.

---

# Project Overview

This project is an Enterprise Hospital Management Information System (E-HMIS) designed to model real-world hospital workflows while keeping future module implementation practical and maintainable.

The system is intended to be modular, scalable, auditable, maintainable, and production-ready.

Future development now follows a **CRUD-first + selective structure** approach. New modules should start with the simplest table, service, pages, permissions, validation, CSRF protection, transactions, and audit trail that satisfy the current workflow. Add extra tables, histories, approvals, settings, or abstractions only when required for security, financial integrity, stock integrity, patient identity, signed clinical records, legal/audit requirements, or an actual current workflow.

Every patient interaction revolves around a single **Encounter (Visit)** which becomes the central object of the entire system.

Phase 1.8 critical pre-clinical remediation is complete. Patient gender uses
the canonical values `Male`, `Female`, `Other`, and `Unknown`; patient mutation
audits are service-owned and transactional; and authentication defaults to
secure production behavior unless development bypass is explicitly enabled in
protected server configuration. Phase 2 Medical Records functionality is
complete through Milestone 2.6. Full patient merging is postponed; the current
MPI duplicate-candidate workflow is sufficient for this version.

---

# Technology Stack

Backend

- PHP 8+
- PDO
- MySQL
- Apache (XAMPP during development)

Frontend

- HTML5
- CSS3
- JavaScript (Vanilla)
- Responsive Design

Architecture

- Service-Oriented
- Thin Controllers
- Modular
- Enterprise Workflow

Version Control

- Git
- GitHub

Development Environment

- Visual Studio Code
- OpenAI Codex
- ChatGPT

---

# Architecture

The application follows a layered architecture.

```
Presentation Layer
    │
Controllers
    │
Business Services
    │
Database
```

Views must never contain SQL.

Controllers must never contain business logic.

Business rules belong inside Services.

---

# Folder Structure

```
authentication/
assets/
config/
database/
layouts/
modules/
services/
```

Example

```
modules/

    patients/

    visits/

    consultation/

    nursing/

    laboratory/

    radiology/

    pharmacy/

    physiotherapy/

    theatre/

    billing/

    records/

    reports/
```

---

# Core Design Principles

This project is a workflow-driven hospital information system with a
CRUD-first implementation policy for future modules.

Every action must:

- validate business rules
- maintain workflow integrity
- create audit history
- update encounter history
- preserve data consistency

For new modules, begin with:

```text
Database Table
-> Service
-> List
-> Create
-> View
-> Edit
-> Save / Update
-> Basic Status
-> Permission
-> CSRF
-> Validation
-> Audit
```

Before creating a new service, table, history table, workflow engine, approval
process, or settings group, first ask whether ordinary CRUD using an existing
service or one simple module service can solve the current requirement.

Clinical narrative information should remain `TEXT` unless the application
needs to independently search, calculate, report, validate, route, or integrate
that information.

Keep as `TEXT` by default:

- history of presenting complaint
- examination findings
- clinical assessment
- treatment plan
- radiology findings
- radiology impression
- nursing narrative
- referral notes

Keep structured:

- patient ID
- visit ID
- doctor
- department
- status
- priority
- dates
- vital-sign measurements
- medication
- test
- amount
- quantity
- stock balance
- payment amount

---

# Coding Standards

Always use

```php
declare(strict_types=1);
```

Use

- PDO only
- Prepared statements
- Transactions for writes

Never

- concatenate SQL
- duplicate business logic
- perform SQL inside views
- place business logic inside controllers

---

# Service Layer

Business logic belongs inside Services.

Examples

```
PatientService

VisitService

AuthService

AuditService

UserService

EncounterEventService

BillingService

LaboratoryService

ConsultationService

NursingService

VitalSignsService

RadiologyService

ECGService

PhysiotherapyService

TheatreService

AccountsService

StoreService

PharmacyService
```

Controllers should simply call services.

---

# Return Convention

All service methods return

```php
[
    'success' => bool,
    'errors' => []
]
```

Additional values may be returned when appropriate.

Example

```php
[
    'success'=>true,
    'visit_id'=>15,
    'visit_number'=>'VIS-20260803-00015',
    'errors'=>[]
]
```

---

# Database Naming

Tables

snake_case

Columns

snake_case

Foreign Keys

xxx_id

Primary Keys

id

---

# PHP Naming

Classes

PascalCase

```
VisitService
```

Methods

camelCase

```
createVisit()

transferVisit()

receiveVisit()

assignDoctor()
```

Variables

camelCase

---

# Core Modules

Completed

Authentication

Patient Management

Visit Management

Transfer Workflow

Receive Workflow

Doctor Assignment

Encounter Timeline

Audit Logging

Encounter Events

---

# Future Modules

Consultation

Nursing

Laboratory

Radiology

ECG

Pharmacy

Accounts

Physiotherapy

Theatre

Medical Records

Inventory

Reports

Administration

Notifications

Appointments

API

---

# Patient Workflow

Patient Registration

↓

Create Encounter

↓

Transfer

↓

Receive

↓

Doctor Assignment

↓

Consultation

↓

Orders

↓

Laboratory

↓

Radiology

↓

Pharmacy

↓

Billing

↓

Discharge

---

# Encounter Workflow

Every encounter is tracked from creation until completion.

Nothing happens outside an encounter.

All modules contribute to the same encounter.

---

# Transfer Workflow

Transfer

↓

Pending

↓

Receive

↓

Department Workspace Opens

Transfers must create

- visit_transfers record
- encounter event
- audit log

---

# Receive Workflow

Receiving a patient

- validates encounter
- validates pending transfer
- marks transfer received
- updates encounter
- records encounter event
- records audit log

---

# Doctor Assignment

Rules

Doctor must exist.

Doctor must belong to current department.

Encounter must be active.

Assignment creates

- encounter event
- audit log

---

# Encounter Timeline

Timeline is generated from

Encounter Creation

Transfers

Receives

Assignments

Consultations

Orders

Laboratory

Radiology

Prescriptions

Billing

Payments

Discharge

Future events should automatically appear.

---

# Audit Logging

Every important workflow action should create an audit record.

Examples

Login

Logout

Transfer

Receive

Assign Doctor

Consultation

Billing

Payment

Discharge

---

# Encounter Events

Every workflow action should create an encounter event.

Future service

```
EncounterEventService
```

will centralize all event creation.

---

# Transactions

Every write operation should use

```php
beginTransaction()

commit()

rollBack()
```

No partial writes.

---

# Security

Always

Validate input.

Escape output.

Use prepared statements.

Verify authentication.

Verify permissions.

Never trust POST data.

---

# Performance

Prefer

JOINs

Indexes

Pagination

Lazy loading

Avoid

N+1 queries

Duplicate SQL

Repeated database calls

---

# UI Philosophy

Enterprise

Minimal

Consistent

Accessible

Responsive

Clear workflow

No unnecessary popups

---

# Current Project Status

Completed

✔ Authentication

✔ Patients

✔ Encounters

✔ Transfers

✔ Receive Workflow

✔ Doctor Assignment

✔ Timeline

✔ Encounter Events

✔ Audit Logs

Current Phase

➡ Laboratory Module

---

# Development Roadmap

Phase 0 through Phase 2 are complete for the current version. Future phases
follow the CRUD-first + selective structure approach.

## Phase 3 - Consultation and Nursing

### 3.1 Consultation CRUD

Primary table: `consultations`.

Initial structured columns:

- `id`
- `visit_id`
- `patient_id`
- `doctor_id`
- `presenting_complaint`
- `history_of_presenting_complaint`
- `examination_findings`
- `assessment`
- `diagnosis_summary`
- `treatment_plan`
- `advice`
- `follow_up_plan`
- `referral_notes`
- `status`
- `created_at`
- `updated_at`

Initial service: `ConsultationService`.

Initial methods:

- `create()`
- `getById()`
- `getByVisit()`
- `update()`
- `complete()`

Initial pages:

- `index.php`
- `create.php`
- `save.php`
- `view.php`
- `edit.php`
- `update.php`
- `complete.php`

Narrative clinical sections remain `TEXT`. Do not create separate tables for
every consultation section.

### 3.2 Vital Signs CRUD

Primary table: `vital_signs`.

Structured values are justified because they need trending and calculation:

- `visit_id`
- `temperature`
- `pulse`
- `respiratory_rate`
- `systolic_bp`
- `diastolic_bp`
- `oxygen_saturation`
- `weight`
- `height`
- `bmi`
- `blood_glucose`
- `recorded_by`
- `recorded_at`

### 3.3 Nursing Assessment CRUD

Use one primary nursing assessment table. Narrative nursing sections remain
`TEXT`; do not normalize every nursing question into its own table.

Phase 3.3 Nursing CRUD is implemented and live-verified. Nursing also owns
the Dressing Book for repeated wound/dressing records and the Drug Chart /
Medication Administration Record for administered doses, plus the DM Sheet for
diabetes monitoring. Phase 3.4 Laboratory
CRUD is implemented and live-verified. Phase 3.5 Radiology CRUD is
implemented and live-verified. ECG is implemented as a simple diagnostic
request/worklist/chart-upload workflow. Phase 3.6 Physiotherapy CRUD is implemented
and live-verified. The next module target after the current
Consultation/Nursing/Laboratory/Radiology/ECG/Physiotherapy slice is now one of
the remaining later operational modules.

## Phase 4 - Radiology

Radiology is implemented with `radiology_requests` and
`radiology_reports`.

Basic workflow:

```text
Request -> Worklist -> Report -> Complete
```

Use text fields for study requested, clinical indication, findings,
impression, and recommendation. Do not build PACS or DICOM integration now.

## Phase 5 - Pharmacy and Inventory

Pharmacy starts with prescriptions and dispensing.

Inventory starts with items and stock transactions. Keep workflows simple, but
protect stock quantities with transactions and audit.

## Phase 6 - Billing

Billing starts with charges, invoices, and payments.

Basic workflow:

```text
Charge -> Invoice -> Payment -> Receipt
```

Postpone advanced insurance and financial approval chains.

## Later / Optional

Postpone advanced analytics, FHIR, HL7, PACS, patient portal, SMS, email
integration, full patient merging, advanced clinical terminology, and complex
approval systems unless current hospital operations require them.

---

# AI Instructions

Before making changes

1. Read this file.

2. Analyze existing architecture.

3. Reuse existing services.

4. Never duplicate logic.

5. Keep controllers thin.

6. Maintain backwards compatibility.

7. Explain architectural changes before implementation.

8. Preserve enterprise workflow.

9. Always create encounter events for workflow actions.

10. Always create audit logs where appropriate.

11. Prefer refactoring over duplication.

12. Ask before introducing breaking database changes.

---

# Code Quality Rules

Never

Duplicate SQL.

Duplicate validation.

Duplicate business logic.

Duplicate workflow.

Prefer reusable methods.

Keep methods focused.

Split large methods when necessary.

Use descriptive names.

Comment complex business rules.

---

# Git Workflow

Before every milestone

```
git add .

git commit -m "Completed Visit Transfer Workflow"
```

Never commit broken code.

---

# Long-Term Goal

Deliver a production-quality Enterprise Hospital Management Information System capable of supporting:

- Multiple departments
- Complete encounter lifecycle
- Electronic Medical Records
- Auditability
- Reporting
- Scalability
- Future mobile/API integration

This repository should evolve toward enterprise software quality rather than a typical academic CRUD project.

## Current Phase 2 Status

Phase 2 is complete for the current version. It includes the Medical Records
foundation, longitudinal Patient Chart, demographic version/history,
patient-aware audit, PHI access logging, MPI and alternate identifiers,
duplicate warnings and candidate review, Clinical Safety, Problem List,
structured Medical History, secure Medical Documents, and Clinical Notes.

Full patient merging is officially postponed. The existing MPI and
duplicate-candidate review workflow blocks exact unsafe duplicates, warns on
strong possible matches, and lets authorized users review candidate pairs
without merging records.

## Recovery checkpoint (2026-08-05)

The development database was reconstructed after previous development records
became unrecoverable during a destructive baseline-import incident. Repository
source and migrations were retained. Reconstruction used selective migrations,
checksums, and deterministic `DEV-*` fixtures; lost records were not recreated
from assumptions.

The verified post-reconstruction baseline backup is
`backups/hms_after_rebuild.sql` (68,606 bytes, created 2026-08-05 05:49:17
Africa/Lagos). It is a readable MariaDB SQL dump. Timestamped later backups may
also contain controlled verification records.

The final verified reconstruction backup, including the controlled completed
workflow verification record, is
`backups/hms_after_rebuild_20260805_061842.sql` (74,598 bytes, created
2026-08-05 06:18:43 Africa/Lagos).

The newest verified post-test recovery point is
`backups/hms_after_rebuild_20260805_062059.sql` (74,666 bytes, created
2026-08-05 06:20:59 Africa/Lagos). Prefer this timestamped dump for restoration.

Create and verify a backup before every migration and another after successful
migration. Abort destructive verification whenever target, test configuration,
or backup state is ambiguous.

Migration 016 was applied through the checksum ledger on 2026-08-05. Its
verified pre-migration backup is
`backups/hms_before_migration_016_20260805_064201.sql` (73,598 bytes), and its
verified post-migration backup is
`backups/hms_after_migration_016_20260805_064344.sql` (74,811 bytes).

## Phase 2 Milestone 2.3 Status

Structured longitudinal allergies, clinical alerts, append-only histories,
confidential-alert masking, and the shared Patient Chart/Encounter Workspace
safety banner are implemented. `ClinicalSafetyService` owns mutations and
audit/history writes. Encounter events require a validated same-patient visit.
Legacy `patients.allergies` remains unchanged and is shown as unverified.

Migration 017 was checksum-applied on 2026-08-05. Verified backups:

- Before: `backups/hms_before_migration_017_20260805_082559.sql` (75,073 bytes).
- After: `backups/hms_after_migration_017_20260805_082643.sql` (88,158 bytes).

Milestone 2.4 and later completed Medical Records capabilities are documented
below.

## Phase 2 Milestone 2.3.1 — Complete

Clinical Safety confidentiality, verification separation, material-edit reset,
inactive/reactivation lifecycle, settings/schema alignment, validated optional
encounter context, fail-closed access auditing, safe history differences, and
dynamic expiry consistency are implemented. Migration 018 was applied through
the checksum ledger in batch 3.

Verified backups:

- Before Migration 018: `backups/hms_before_migration_018_20260805_120934.sql`
  (93,291 bytes).
- After Migration 018: `backups/hms_after_migration_018_20260805_121804.sql`
  (94,204 bytes).

The dedicated test database remained isolated from
`hospital_management_system`; Phase 0–2.3 regression suites passed.

## Phase 2 Milestone 2.4 — Complete

The longitudinal Problem List and structured Medical History are implemented
through `ProblemListService`, Patient Chart sections, and read-only Encounter
Workspace summaries. Current records use optimistic versions; clinical changes
append immutable snapshots and transactional patient-aware audit records.
Validated optional encounter context produces encounter events without making
longitudinal records dependent on a visit. Consultation diagnoses and automatic
diagnosis promotion were not implemented.

Migration 019 was checksum-applied to `hospital_management_system` in batch 4
on 2026-08-05 14:24:57 Africa/Lagos. Verified recovery points:

- Before: `backups/hms_before_migration_019_20260805_140955.sql` (94,651 bytes).
- After: `backups/hms_after_migration_019_20260805_142548.sql` (110,578 bytes).

Dedicated-database service, migration-cycle, Phase 0–2.3.1 regression, schema,
foreign-key, index and PHP syntax checks passed. Unauthenticated HTTP routes
redirect to login as expected. Authenticated live HTTP verification passed for
the administrator dashboard, Problem List and Medical History Patient Chart
tabs, and Encounter Workspace. A protected mutation without CSRF returned HTTP
403. Credentials are intentionally not documented.

## Phase 2 Milestone 2.5 — Complete

Secure patient-level and encounter-linked Medical Documents are implemented
through `MedicalDocumentService`, `DocumentStorageInterface`, and
`SecureLocalDocumentStorage`. Metadata and immutable versions are in MySQL;
file bytes are held under the protected external root
`C:\xampp\hms_secure_documents`. Downloads are controller-mediated and checked
for authorization, confidentiality, lifecycle, checksum, audit, and PHI access.
The application `storage/` directory is also protected with a deny-all
`.htaccess` so logs, sessions, and test artifacts are not browsable from Apache.
Unused public-looking `assets/uploads/` and `assets/documents/` directories are
also blocked, and `database/` is deny-all so SQL backups cannot be downloaded
by URL.
No Clinical Notes or later Phase 2 feature was introduced.

Migration 020 was checksum-applied on 2026-08-05 15:59:38 Africa/Lagos.
Verified recovery points created in this session are:

- Before: `backups/hms_before_migration_020_20260805_155217.sql`
  (112,499 bytes).
- After: `backups/hms_after_migration_020_20260805_160028.sql`
  (124,341 bytes).

The dedicated test database passed focused storage/service tests, isolated
Migration 020 down/up verification, and Phase 0–2.4 regressions. Live HTTP
verification passed login, Patient Chart Documents, Encounter Workspace
Documents, and CSRF refusal. Deployment requirements are in
`MEDICAL_DOCUMENTS_DEPLOYMENT.md`.

## Current status — Phase 2 Milestone 2.6

Milestone 2.6 is implemented and live-verified. Clinical Notes support
patient/encounter scope, immutable draft and signed versions, Doctor signing,
locking, amendment approval through `record_amendments`, entered-in-error,
confidentiality, Patient Chart and Workspace integration, PHI access logging,
and optional encounter events. Migration 021 is ledger-applied. Verified
recovery points are
`backups/hms_before_migration_021_20260805_173844.sql` and
`backups/hms_after_migration_021_20260805_174602.sql`. See
`CLINICAL_NOTES_ARCHITECTURE.md`.

## Phase 2 Lean Closeout

Phase 2 closes without implementing the previously planned complex Patient
Merge milestone. No `PatientMergeService`, survivor/canonical-patient logic,
merge approval workflow, merge reversal, merge-history infrastructure,
foreign-key remapping, canonical chart aggregation, or additional duplicate
scoring engine is part of the current version.

Remaining non-blocking items are categorized as **LATER**, not blockers:

- full patient merge
- break-glass access
- co-signatures
- controlled clinical terminology
- advanced full-text search
- object storage
- real malware scanner integration
- retention automation
- browser automation
- multi-process concurrency testing
- sophisticated duplicate matching
- FHIR, HL7, PACS, patient portal, SMS, and email integrations
- advanced reporting infrastructure

Phase 3.6 Physiotherapy CRUD is implemented through
`PhysiotherapyService`, encounter/workspace integration, patient-chart
history, clinical/direct referral flows, session records, and audit/timeline
events.

The next implementation target after the current Phase 3 slice is one of the
remaining later operational modules, not Physiotherapy.

## Phase 3 Milestone 3.1 — Consultation and Department Notifications

Consultation is implemented as an encounter-centered CRUD module using one
`consultations` table and `ConsultationService`. It preserves the assigned
clinical doctor separately from the acting user: if an administrator creates or
completes the consultation during development/testing, `doctor_id` remains the
encounter doctor while `created_by`, `updated_by`, or `completed_by` stores the
administrator.

Department notifications are implemented through one
`department_notifications` table and `DepartmentNotificationService`. They
request attention from another department only and do not transfer encounters,
change queue ownership, or alter current department ownership.

The Consultation UI uses a review step before saving a draft record, then
returns to the consultation view and Workspace tab. The Encounter Workspace
header also exposes a top-right `Complete Visit` action that posts through the
existing encounter status workflow.

Phase 3.1 remains CRUD-first: no diagnosis tables, nursing workflow,
laboratory/radiology/pharmacy/billing functionality, patient merge, templates,
co-signatures, autosave, or additional architecture document was introduced.

## Phase 3.2 â€” Vital Signs CRUD

**Implemented.** Vital Signs uses one `vital_signs` table, one
`VitalSignsService`, encounter/status locking, BMI calculation, audit logging,
patient-chart history, Encounter Workspace integration, and read-only
consultation context display. Doctor and Nurse can create/update/view, while
other roles receive only explicitly permitted read access.

Phase 3.2 does not introduce nursing, laboratory, radiology, pharmacy,
billing, or additional workflow engines. It is a straightforward encounter-
centered CRUD module.

Accounts / Price Catalogue is now implemented as a standalone sidebar module.
It owns the hospital-wide price catalogue only; Billing/Charges will later
consume those prices at the encounter level.

Store / Inventory is also implemented as a standalone sidebar module. It owns
stock receipts, issues, returns, adjustments, department balances, and the
inventory item catalogue. Store does not live inside the Encounter Workspace,
and it does not create patient charges or dispensing workflows. Store also
supports simple external walk-in sales for non-patient customers: the sale uses
Accounts catalogue price snapshots, reduces Store stock through the stock
ledger, and produces a printable receipt without creating a patient encounter,
invoice, or patient charge.

Department Stock Requests are implemented as a standalone sidebar module.
Departments can request stock from Store without entering the Store module
directly. Requests do not change stock; stock changes only when Store/Admin
issues items through the existing Store stock ledger.

Patient Stock Usage is implemented as encounter-linked department consumption.
Departments can record items used on a specific patient encounter from their
own department stock. The record reduces department balance through
`StoreService` using an immutable `Consumption` stock transaction, and can
optionally create a Billing Request for Accounts review. It does not create a
patient charge directly.

## Phase 4.5 Basic Dashboards / Reports

Phase 4.5 adds a small read-only Reports module and enhances the
Administrator dashboard with current clinical activity, financial, inventory,
and notification summaries. This is not a BI platform: there are no scheduled
reports, report designer, warehouse tables, chart-library dependencies, or
advanced analytics.

The main user dashboard is department-scoped for non-administrators: current
working encounters and active encounter counts show only the user's
active/current department. System Administrator retains the hospital-wide
operational dashboard view.

Printable register/report views now include Emergency Register, Laboratory
Report Book, Radiology Report Book, and Theatre Operation Register. These are
read-only views over existing operational records, not new workflow modules.

## Current implementation checkpoint after Phase 4.5

The current operational application includes:

- Authentication, session handling, administrator password reset, user/role/
  permission/department administration, settings, and audit/security views.
- Patient registration/search, MPI duplicate-candidate review, expanded
  demographics, patient chart, identifiers, demographic history, clinical
  safety, Blood Card summary, problems, structured medical history, documents,
  and clinical notes.
- Encounter creation, workspace, transfer/receive, doctor assignment,
  department notifications, timeline, cancellation, and completion/discharge
  capture.
- Consultation, Vital Signs, Nursing with Dressing Book, Drug Chart, and DM Sheet,
  Laboratory, Radiology, ECG, POP, Physiotherapy,
  Theatre, Pharmacy, Billing with department billing requests,
  Accounts/Price Catalogue, Store/Inventory, and
  Basic Reports/Dashboard summaries.
- Inpatient Admissions / Ward & Bed workflow: Nurse, Doctor, Receptionist,
  Records Officer, and Administrator users can admit an active encounter to a
  ward/bed and transfer/change ward-bed placement. Discharge/cancel admission
  and ward/bed master-data setup remain controlled by their separate
  permissions.
- Clinical cross-view permissions from Migration 038 plus ECG/POP additions are live: Doctor, Nurse,
  Laboratory Scientist, Radiographer/X-Ray, ECG Technician, POP Technician, Physiotherapist, Theatre Staff,
  Pharmacist, and Records Officer can view core clinical context across the
  active encounter while create/edit/complete rights remain owned by each
  department module. Vital Signs mutation remains limited to Doctor, Nurse, and
  Administrator.

POP is implemented as a simple encounter-linked department workflow through
`pop_requests`, `pop_records`, and `POPService`. Doctors can create clinical
POP/casting requests. POP Technicians can create direct POP requests for active
POP encounters, process their worklist, document cast/procedure details,
materials used, aftercare, and remarks, then complete the request. POP does
not require fake Consultation records for direct patients.

Consultation supports both typed narrative entry and an optional Doctor/Admin
handwriting pad. The handwriting feature stores compact stroke data in the
existing narrative fields and renders it back on review/view; it does not
perform OCR or convert handwriting into typed text.

## Phase 5.0 Final Integration / Regression / Production Hardening

Phase 5.0 is a bug-fix, regression, and production-readiness pass rather than a
new feature phase. The initial hardening pass verified PHP syntax across the
repository, migration ledger status through Migration 053, selected
non-destructive route/layout helpers, sidebar visibility, Phase 3 clinical
modules, Phase 4 operational/financial modules, and Admissions against the
dedicated test database.

Production error display is centrally controlled from `config/app.php`.
Missing/invalid `HMS_APP_ENV` resolves to production, where PHP logs errors but
does not display them to browser users. Development/testing keep error display
enabled for debugging. By default, PHP runtime errors are written to
`storage/logs/php_errors.log` when `storage/logs` is writable. Deployments may
override this with `HMS_PHP_ERROR_LOG`.

A backup restore drill was completed on 2026-08-26 by restoring
`database/backups/before_053_patient_stock_usage_20260825_115230.sql` into the
separate `hms_restore_test` database. Core tables and row counts were verified,
and the live `hospital_management_system` database was not modified.

System Administrators now have a guarded browser backup page at
`Administration -> Database Safety -> Backup Database`. It creates verified
timestamped SQL dumps in `database/backups/`, records the action in
`storage/logs/database_operations.log`, and writes an Administration audit log.
Database restore remains manual/CLI-only and must be tested against a separate
restore database rather than the live application database.

Verified blockers fixed during the pass:

- `StoreService::recordMovement()` no longer references an undefined
  `$ownsTransaction` variable; normal Store receive/issue/return/adjust flows
  commit or roll back correctly.
- `PatientService` now checks whether the soft-delete column exists before
  adding `is_deleted` filters to search queries. Live behavior still hides
  deleted patients, while older migration-cycle test schemas no longer fatal.
- Small CLI test harnesses now use `__DIR__`-based includes so they run from
  the project root.

Known practical cleanup items that are not blockers:

- `authentication/forgot_password.php` is not implemented; account recovery is
  currently handled by administrator password reset.
- legacy dashboard, `includes/`, and `workspace/` stub/redirect files remain
  compatibility routes. The active Encounter Workspace is
  `modules/visits/workspace.php`.
- Consultation handwriting mode should still be manually smoke-tested in a
  browser because it is a pointer/canvas interaction.

## Administrator model

The application distinguishes Super Administrator from ordinary System
Administrator.

- Super Administrator has full-system override access.
- System Administrator is limited to Administration functionality such as user,
  role, permission, department, security/settings management, plus the ability
  to view other department worklists for operational support.
- Walter Ikhile / username `walter` is the protected Super Administrator
  account.
- The original `admin` account remains an ordinary System Administrator.
