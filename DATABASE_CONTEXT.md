# Database Context

> **Read this document before modifying the database schema or writing SQL.**
>
> This document defines the database architecture, naming conventions, relationships, integrity rules, indexing strategy, and future expansion plan for the Enterprise Hospital Management Information System (E-HMIS).

---

# Database Engine

Database

```
MySQL 8+
```

Storage Engine

```
InnoDB
```

Character Set

```
utf8mb4
```

Collation

```
utf8mb4_unicode_ci
```

Timezone

```
UTC (recommended)
```

---

# Database Design Philosophy

The database is designed around one central concept:

```
Patient
    ↓
Encounter (Visit)
    ↓
Clinical Activities
```

Nothing happens outside an encounter.

Every consultation, laboratory request, prescription, transfer, billing record, or discharge belongs to a Visit.

---

# Naming Standards

## Tables

Use plural names.

Examples

```
patients

visits

departments

users

roles

audit_logs

visit_transfers

encounter_events
```

---

## Primary Keys

Always

```
id
```

Example

```
patients.id
```

---

## Foreign Keys

Always

```
xxx_id
```

Example

```
patient_id

visit_id

doctor_id

department_id

created_by

updated_by
```

---

## Date Columns

Use

```
created_at

updated_at
```

Workflow timestamps should use descriptive names.

Examples

```
received_at

transferred_at

assigned_at

discharged_at
```

---

# Core Tables

## departments

Purpose

Defines all hospital departments.

Examples

```
Reception

Records

Doctor

Nursing

Laboratory

Radiology

Pharmacy

Accounts

Physiotherapy

Theatre

Store

Administration
```

Primary Key

```
id
```

Important Columns

```
department_name

description
```

---

## roles

Purpose

Defines user roles.

Examples

```
System Administrator

Receptionist

Doctor

Nurse

Laboratory Scientist

Radiographer

Pharmacist

Cashier

Store Officer
```

---

## users

Purpose

System users.

Relationships

```
department_id

role_id
```

Future Columns

```
is_active

last_login

password_changed_at

failed_login_attempts

must_change_password
```

---

## patients

Purpose

Stores permanent patient demographic information.

Patient gender is constrained consistently across the baseline schema,
migration path, `PatientService`, and patient forms. The supported values are:

```
Male
Female
Other
Unknown
```

`Unknown` is the explicit value for unavailable historical data. Migration 012
records legacy empty ENUM sentinels in `phase1_patient_gender_repair` before
repairing them to `Unknown`; valid patient values are not rewritten.

Examples

```
Hospital Number

Name

Gender

DOB

Address

Phone

Next of Kin
```

One patient

↓

Many encounters

---

## visits

Purpose

Central encounter table.

This is the heart of the system.

Every workflow references this table.

Relationships

```
patient_id

current_department_id

attending_doctor_id

created_by

current_department_received_by
```

Current workflow fields

```
visit_number

visit_type

visit_date

visit_status

queue_number

current_department_id

attending_doctor_id

current_department_received_status

current_department_received_by

current_department_received_at
```

---

# visit_transfers

Purpose

Complete transfer history.

One encounter

↓

Many transfers

Columns

```
visit_id

from_department_id

to_department_id

transfer_type

remarks

transferred_by

transferred_at

received_by

received_at
```

Transfer Types

```
Forward

Return

Referral

Discharge

Completion

Cancellation
```

---

# encounter_events

Purpose

Chronological history of everything that happens during an encounter.

Examples

```
Encounter Created

Transferred

Patient Received

Doctor Assigned

Consultation Started

Laboratory Requested

Prescription Issued

Invoice Generated

Payment Received

Discharged
```

Columns

```
visit_id

event_type

event_title

event_description

department_id

performed_by

event_time
```

---

# audit_logs

Purpose

System audit trail.

Unlike encounter events,

Audit Logs describe

WHO performed

WHAT action

WHEN

FROM WHICH IP

Examples

```
Login

Logout

Transfer

Receive

Delete

Update

Assign Doctor
```

---

# Implemented Phase 1 Configuration Tables

## system_settings

Purpose

Stores the centralized typed enterprise configuration catalogue. Settings use a unique `setting_key`, category, type, current/default values, JSON validation rules, visibility/editability flags, sensitive/encryption metadata, ordering, and creator/updater relationships.

All application access must use `SettingsService`; controllers and views must not query this table directly.

---

## system_setting_history

Purpose

Stores immutable setting create, update, reset, and delete history. It retains setting key/category snapshots and old/new values. Sensitive values are stored as `[REDACTED]`.

One setting

â†“

Many history entries

---

# Future Tables

## consultations

One visit

↓

One laboratory request

Relationship

```
visit_id
```

---

## consultation_diagnoses

One consultation

↓

Many diagnoses

---

## consultation_notes

One consultation

↓

Many notes

---

## prescriptions

One consultation

↓

Many prescriptions

---

## laboratory_requests

One consultation

↓

Many requests

---

## laboratory_results

One laboratory request

↓

One result record with sample details, findings, result text, and interpretation

---

## radiology_requests

One radiology request

↓

One text-based report workflow record linked to the same visit and patient

## radiology_reports

One report record with findings, impression, recommendation, reporter, and completion timestamps

## nursing_assessments

---

## physiotherapy_sessions

---

## theatre_operations

---

## invoices

---

## invoice_items

---

## payments

---

## discharge_summaries

---

## medical_documents

---

# Relationship Overview

```
Patients

    |

    | 1

    |

    |------< Visits

                    |

                    |------< Visit Transfers

                    |

                    |------< Encounter Events

                    |

                    |------< Consultations

                    |

                    |------< Laboratory Requests

                    |

                    |------< Prescriptions

                    |

                    |------< Billing

                    |

                    |------< Discharge
```

---

# Workflow Relationships

Patient

↓

Visit

↓

Transfer

↓

Receive

↓

Assign Doctor

↓

Consultation

↓

Orders

↓

Results

↓

Billing

↓

Discharge

Every step references

```
visit_id
```

---

# Foreign Key Rules

Always enforce

```
ON UPDATE CASCADE
```

Prefer

```
ON DELETE RESTRICT
```

Never automatically delete

Patient

Visit

Clinical History

Audit History

---

# Soft Delete Policy

Never physically delete

Patients

Visits

Consultations

Laboratory Results

Invoices

Instead use

```
is_deleted

deleted_at

deleted_by
```

where applicable.

---

# Enumerations

## visit_status

Current

```
Waiting

Reception

Records

Doctor

Nursing

Laboratory

Radiology

Pharmacy

Accounts

Completed

Cancelled
```

Future

```
Discharged
```

---

## current_department_received_status

```
Pending

Received
```

---

## transfer_type

```
Forward

Return

Referral

Discharge

Completion

Cancellation
```

---

# Index Strategy

Index every

Foreign Key

Frequently searched field

Unique identifier

Examples

```
patient_id

visit_id

department_id

doctor_id

hospital_number

visit_number
```

Composite indexes

Examples

```
(visit_id,event_time)

(current_department_id,current_department_received_status)

(patient_id,visit_date)
```

---

# Transaction Rules

Every write operation must use

```
BEGIN

COMMIT

ROLLBACK
```

Examples

Transfer

Receive

Doctor Assignment

Consultation

Billing

Discharge

---

# Audit Rules

Every important workflow writes

Audit Log

Encounter Event

Never update without history.

---

# Reporting Philosophy

Reports should never reconstruct history from the current state.

Always use

```
encounter_events

visit_transfers

audit_logs
```

These are immutable historical records.

---

# Future Scalability

Database should support

Multi-hospital

Multi-branch

Appointment scheduling

Bed management

Inventory

Insurance

Electronic prescriptions

Patient portal

REST API

Mobile application

Analytics

Business Intelligence

without redesigning the core schema.

---

# AI Instructions

Before changing the database

1. Preserve referential integrity.

2. Preserve audit history.

3. Never remove historical records.

4. Prefer additive migrations over destructive changes.

5. Explain migration impact before implementation.

6. Add indexes for new foreign keys.

7. Document every new table in this file.

8. Ensure every clinical table references `visit_id` unless there is a strong architectural reason not to.

This database is intended to evolve into a production-quality Enterprise Electronic Medical Record (EMR), prioritizing workflow integrity, auditability, and long-term maintainability over short-term convenience.

## Phase 2 Milestone 2.2 Schema

Migration 016 formally adopts the MPI structures preserved from Migration 014
and is safe when those structures already exist. It introduces no merge table
and deletes no patient data.

| Table/change | Purpose | Integrity policy |
|---|---|---|
| `patients.normalized_*` | Indexed name, phone and email matching values | Service-maintained; original values remain authoritative for display. |
| `patient_identifiers` | Current alternate identifiers | Restrictive FKs, scoped unique key, one primary per patient/type, version counter. |
| `patient_identifier_history` | Identifier snapshots | Append-only; unique identifier/version; restrictive FKs. |
| `patient_duplicate_candidates` | Ordered possible-duplicate pairs | Unique low/high pair and ordering check; review has no merge side effect. |

Search order is exact hospital number, exact active alternate identifier, exact
normalized phone, normalized name prefix, then normalized phone prefix.
Primary searches do not use leading wildcards. The bounded fuzzy threshold is
configured, but fuzzy expansion remains disabled pending production-volume
query-plan and false-positive validation.

## Phase 2 Milestone 2.1 Schema

Migration 013 adds `patients.demographic_version`, `audit_logs.patient_id`,
`record_amendments`, `patient_demographic_history`, and `record_access_logs`.
Demographic and access histories are retained; ordinary workflows do not
delete them. Identifiers, allergies, alerts, problems, documents, notes, and
merge tables remain planned and are not part of this milestone.

## Reconstruction safety checkpoint (2026-08-05)

Previous development rows were lost when a baseline with hardcoded live
database deletion/selection statements was imported during verification.
Binary logging was disabled and no backup existed, so recovery was unavailable.
The current database was reconstructed from repository schema and migrations;
no lost clinical records were invented.

The schema now has a checksum-backed `schema_migrations` ledger. Automated
tests use `database/schema.sql`, `config/test_database.php`, and guarded CLI
tools. Test databases must be explicit, named `hms_test_*`, and distinct from
live. Destructive recreation requires a verified backup or explicit approval.

# Phase 2 Clinical Safety Tables

Migration 017 adds four InnoDB/`utf8mb4_unicode_ci` tables:

| Table | Status | Purpose |
|---|---|---|
| `patient_allergies` | Mutable, versioned current state | Structured longitudinal allergies, verification, and resolution |
| `patient_allergy_history` | Append-only | Previous/new allergy snapshots and reasons by version |
| `patient_alerts` | Mutable, versioned current state | Effective-dated safety alerts and confidentiality classification |
| `patient_alert_history` | Append-only | Previous/new alert snapshots and reasons by version |

Patient foreign keys use `ON DELETE RESTRICT`. Optional visit links must belong
to the same patient, enforced under a row lock by `ClinicalSafetyService`.
Unique nullable active keys prevent concurrent duplicate active records while
allowing resolved/closed history. Migration 017 was checksum-applied live on
2026-08-05. `hospital.sql` remains the release baseline; this post-baseline
change is delivered through the numbered migration path.

### Migration 018 — Clinical Safety hardening

Migration 018 was checksum-applied live in batch 3 on 2026-08-05. It adds
`clinical_safety.allow_self_allergy_verification=false` and `schema_values`
validation metadata for five Clinical Safety array settings. Existing allergy,
alert, and history table structures are unchanged. Snapshot JSON already stores
the confidentiality classification required for per-version authorization.
The down migration is data-preserving and restores the prior settings metadata.

## Migration 019 — Problem List and Medical History

**Applied:** 2026-08-05, ledger batch 4.

Migration 019 adds `patient_problems`, `patient_problem_history`,
`patient_medical_history`, and `patient_medical_history_versions`. All use
InnoDB/utf8mb4, restrictive patient/visit foreign keys, actor attribution,
optimistic versions, and indexed patient/status/time access paths. Current
records are mutable only through versioned service operations; history tables
are append-only. `hospital.sql` and `schema.sql` contain the table DDL, while
Migration 019 remains required after Administration migrations to seed its
permissions and settings in dependency order.

The down migration destroys retained clinical history and is test-only unless
an approved archival/recovery plan exists. Verified recovery points are
`backups/hms_before_migration_019_20260805_140955.sql` and
`backups/hms_after_migration_019_20260805_142548.sql`.

## Migration 020 — Secure Medical Documents

**Applied:** 2026-08-05 15:59:38 Africa/Lagos through `schema_migrations`.

Migration 020 adds `medical_documents` and append-only
`medical_document_versions`. Patient and optional visit references use
`ON DELETE RESTRICT`; department is nullable with `SET NULL`; actor and
supersession references are restrictive. Logical records use optimistic
versions. File versions have unique `(document_id, version_number)` and opaque
storage keys plus indexed checksum/status access paths. File bytes remain in
protected storage outside MySQL.

Fresh-install `hospital.sql` and automation-neutral `schema.sql` include both
tables. Migration 020 remains ordered after Administration migrations to seed
seven permissions and eleven settings. Its down migration is destructive to
retained metadata and is approved only for an empty isolated test database or
an explicit archival/recovery plan.

Verified recovery points:

- `backups/hms_before_migration_020_20260805_155217.sql`
- `backups/hms_after_migration_020_20260805_160028.sql`

## Migration 021 — Clinical Notes

**Applied 2026-08-05.** Migration 021 adds `clinical_notes` as current
metadata and `clinical_note_versions` as immutable content history. It reuses
`record_amendments`; it creates no Consultation, Nursing, diagnosis, or merge
schema. Patient/visit/user references are deletion-restricted, department
references use `SET NULL`, and `(note_id, version_number)` is unique.

Verified recovery points:

- `backups/hms_before_migration_021_20260805_173844.sql` (126,862 bytes)
- `backups/hms_after_migration_021_20260805_174602.sql` (138,624 bytes)

## Migration 022 — Consultation and Department Notifications

Migration 022 adds the Phase 3.1 CRUD-first operational tables:

- `consultations`: one consultation per visit, linked to patient, clinical
  doctor, optional department, and actor fields for create/update/complete.
- `department_notifications`: attention requests between departments linked to
  visit, patient, sender, source department, destination department, and simple
  Unread/Read/Resolved status.

The migration also seeds the four Consultation permissions and grants them to
the Doctor role. It intentionally creates no diagnosis, nursing, laboratory,
radiology, pharmacy, billing, patient-merge, note, or history/version tables.
The down migration drops Phase 3.1 data and is therefore restricted to
dedicated disposable test databases or an explicitly approved recovery action.

## Phase 3.2 Vital Signs

Migration 023 adds the encounter-linked `vital_signs` table for routine
measurements. It stores patient, visit, department, recorder, numeric
measurements, BMI, and notes with `ON DELETE RESTRICT` on patient/visit/user
links and `SET NULL` on department context. Indexed access paths support visit,
patient, recorder, department, and chronology queries. The table is mutable
current state; the application allows multiple records per visit and the
workspace, patient-chart and consultation consumers all read the latest record
as a summary. The down migration is destructive to retained encounter history
and is restricted to approved empty test databases or a verified recovery
action.

## Phase 3.3 Nursing Assessment

Migration 024 adds the encounter-linked `nursing_assessments` table for a
simple primary nursing assessment per visit. It stores the patient, visit,
nurse, department, narrative assessment fields, Draft/Completed status, actor
fields, timestamps, and a completed-at marker. The table uses restrictive
patient/visit foreign keys, a unique visit constraint, and indexes that support
patient, nurse, department, status, and chronology queries. The down migration
removes retained nursing assessment data and is therefore restricted to empty
test databases or an explicitly approved archival recovery procedure.

## Phase 3.6 Physiotherapy

Migration 028 adds the encounter-linked `physiotherapy_records` and
`physiotherapy_sessions` tables. The record table stores the patient, visit,
physiotherapist, department, record source, referral reason, presenting
problem, assessment, treatment plan, goals, precautions, status, actor fields,
and timestamps. The session table stores multiple follow-up treatments per
record with session date, treatment given, patient response, progress notes,
next plan, and recorder metadata. Both tables use restrictive patient/visit
links, support active record and chronology queries, and preserve encounter
history without introducing a more complex rehabilitation workflow.

## Phase 3.7 Theatre

Migration 029 adds the encounter-linked `theatre_records` table. It stores
the patient, visit, surgeon, department, procedure name, indication,
preoperative notes, procedure details, findings, complications,
postoperative notes, postoperative plan, anaesthesia notes, Draft/Completed
status, actor fields, and timestamps. The table uses restrictive
patient/visit foreign keys, a unique visit constraint, and indexes that
support patient, surgeon, department, status, and chronology queries. The
down migration removes retained theatre data and is therefore restricted to
empty test databases or an explicitly approved archival recovery procedure.

## Phase 4.1 Accounts / Price Catalogue

Migration 030 adds the standalone `billable_items` price catalogue. The table
stores an item code, item name, item type, optional department context,
description, current unit price, optional unit, soft activation state, actor
attribution and timestamps. It uses restrictive foreign keys to departments
and users and is consumed by the Accounts sidebar module only. There is no
patient, visit, charge, invoice, payment, or receipt table in this phase.
