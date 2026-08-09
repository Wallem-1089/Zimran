# Database Evolution Policy

`database/hospital.sql` is the baseline schema for a fresh installation.

After a release has been installed, all schema changes must be delivered as
numbered, paired migrations:

- `<number>_<name>_up.sql` applies the change.
- `<number>_<name>_down.sql` reverses the change when it is safe to do so.

The current migration order is:

1. `002_phase0_live_schema_alignment_up.sql`
2. `003_phase0_queue_workflow_up.sql`
3. `004_phase0_store_status_up.sql`
4. `005_phase1_user_management_up.sql`
5. `006_phase1_roles_permissions_up.sql`
6. `007_phase1_departments_assignments_up.sql`
7. `008_phase1_security_administration_up.sql`
8. `009_phase1_system_settings_up.sql`
9. `010_phase1_production_indexes_up.sql`
10. `011_phase1_visit_status_repair_up.sql`
11. `012_phase1_patient_gender_remediation_up.sql`
12. `013_phase2_medical_records_foundation_up.sql`
13. `014_phase2_mpi_identifiers_up.sql`
14. `015_recovery_safety_and_seed_reconciliation_up.sql`
15. `016_phase2_patient_identifiers_mpi_up.sql`
16. `017_phase2_clinical_safety_up.sql`
17. `018_phase2_clinical_safety_hardening_up.sql`
18. `019_phase2_problem_list_medical_history_up.sql`
19. `020_phase2_medical_documents_up.sql`
20. `021_phase2_clinical_notes_up.sql`
21. `022_phase3_consultation_notifications_up.sql`
22. `023_phase3_vital_signs_up.sql`
23. `024_phase3_nursing_up.sql`
24. `025_phase3_laboratory_up.sql`

The missing `001` number is historical and is intentionally not reused.
Migration files are not replayed against an already aligned database.
`schema_migrations` records applied filenames and checksums; checksum changes
fail closed. Historical alignment files still require schema preconditions and
must not be executed directly outside the guarded process.

The baseline includes the final Phase 0 schema so fresh installations do not
need to replay the alignment migrations. Existing installations use the
migrations to reach that same schema without deleting data.

Down migrations must be reviewed against live data before execution. In
particular, rolling back the Store status or dropping encounter events can
require data retention or archival work and must not be treated as an
automatic production rollback.

Migration 012 expands `patients.gender` to `Male`, `Female`, `Other`, and
`Unknown`. It records historical empty ENUM sentinels before repairing them to
`Unknown`. Its down migration deliberately refuses to contract the enum while
any `Other` or `Unknown` patient remains, preventing silent demographic data
conversion.

Migration 013 establishes the Phase 2 Medical Records foundation. It adds
optimistic demographic versioning, append-only demographic and amendment
history, patient-aware audit references, protected-health-information access
logs, and the initial Medical Records permissions. It intentionally does not
create identifiers, allergies, alerts, problems, documents, notes, or merge
tables. Its down migration is structurally reversible but destroys retained
history and therefore requires explicit archival review in production.

## Recovery baseline, ledger, and safety

`database/hospital.sql` is the manual database-neutral baseline and
`database/schema.sql` is its automation-safe counterpart. Neither may contain
database creation, deletion, or hardcoded selection statements.

The baseline represents migrations 002, 003, 004, 013, and 014 structurally.
A fresh reconstruction imports `schema.sql`, records those migrations as
baseline-represented, applies 005 through 012 in order, then applies 015 to
reconcile the checksum ledger, Medical Records grants, and retained MPI
settings. Migration 016 then formally adopts and verifies the retained Phase
2.2 structures and seeds its additive settings/permissions.

`schema_migrations` records filename, SHA-256 checksum, batch, timestamp, and
execution time. Checksum changes fail closed. Migration 015 intentionally has
no destructive automatic rollback.

Database tests require `HMS_APP_ENV=testing`, an explicit distinct
`hms_test_[a-z0-9_]+` database, an explicit recreation flag, and a verified
backup or explicit disposable-test acknowledgement. Guarded tools print both
database names, preflight SQL, and log every destructive operation.

Migration 016 owns the formal Milestone 2.2 release boundary. Because
Migration 014 and the current baseline already contain identifier and
duplicate-review structures, its up migration uses additive adoption and its
down migration removes only four 016-owned settings. It never drops patient,
identifier, history, or duplicate-case data automatically.

Migration 017 establishes structured longitudinal allergies and clinical
alerts with append-only histories, clinical-safety permissions/settings, and
optional validated encounter linkage. Its down migration destroys retained
clinical-safety data and is approved only for empty isolated verification
databases unless an explicit archival and recovery plan has been approved.

Migration 018 hardens Clinical Safety without rebuilding clinical tables. It
adds the secure-default self-verification policy and constrains editable
Clinical Safety value-list settings to schema-supported ENUM values. Its down
migration removes only the new policy setting and restores the earlier generic
required-array validation metadata; it does not modify clinical records.

Migration 019 establishes the longitudinal Problem List and structured Medical
History. Current-state tables use optimistic versions and restrictive patient
and encounter foreign keys; companion history/version tables are append-only.
Fresh-install baselines include the table DDL, but Migration 019 remains
ledger-applied after the Administration migrations so permission and setting
seeds execute only after their owning tables exist. Its idempotent table DDL
makes that ordered application safe. The down migration drops retained clinical
history and is therefore restricted to empty, isolated test databases or an
explicitly approved archival procedure.

Migration 020 establishes secure Medical Document metadata and immutable file
versions. The baseline includes both tables while the ordered migration seeds
permissions and settings after their owning Administration tables exist. Files
remain outside MySQL and are referenced only by opaque storage keys. Its down
migration destroys retained document metadata and version references, so it is
restricted to an empty isolated test database or an explicitly approved,
verified archival and recovery procedure.

## Migration 021 — Phase 2.6 Clinical Notes

`021_phase2_clinical_notes_up.sql` creates `clinical_notes` and immutable
`clinical_note_versions`, seeds 11 permissions and 11 validated settings, and
reuses `record_amendments`. It creates no Consultation, Nursing, diagnosis,
document, or merge table. Both fresh-install baselines represent its tables.

The down migration removes Clinical Note amendment rows, tables, grants,
permission entries, and settings. It is destructive and restricted to an empty
dedicated test database or an explicitly approved recovery operation after the
backup gate. The isolated cycle and checksum verification passed on 2026-08-05.

## Migration 022 — Phase 3.1 Consultation and Department Notifications

`022_phase3_consultation_notifications_up.sql` creates one encounter-centered
`consultations` table and one `department_notifications` table. It seeds the
four Consultation permissions (`view_consultation`, `create_consultation`,
`edit_consultation`, `complete_consultation`) and grants them to the Doctor
role. The implementation intentionally keeps Consultation as straightforward
CRUD with narrative `TEXT` fields and does not create diagnosis, nursing,
laboratory, radiology, pharmacy, billing, clinical-note, or patient-merge
structures.

Department notifications request attention from another department only. They
do not transfer the encounter, change queue ownership, or alter the current
department. The down migration removes the Phase 3.1 tables and Consultation
permission seeds, so it is restricted to an empty dedicated test database or an
explicitly approved recovery operation after the backup gate.

## Migration 023 â€” Phase 3.2 Vital Signs

`023_phase3_vital_signs_up.sql` creates one encounter-linked `vital_signs`
table for routine measurements and seeds the three Vital Signs permissions
(`view_vital_signs`, `create_vital_signs`, `edit_vital_signs`). Doctor and
Nurse receive full Vital Signs CRUD; Records Officer receives view-only
access. The baseline files already represent the table DDL, while the numbered
migration preserves the ledgered release boundary for existing installations.

The down migration removes the retained Vital Signs table and permission
seeds. It is destructive and restricted to an empty dedicated test database or
an explicitly approved recovery operation after the backup gate.

## Migration 024 â€” Phase 3.3 Nursing Assessment

`024_phase3_nursing_up.sql` creates one primary `nursing_assessments` table
per visit and seeds the four Nursing permissions (`view_nursing`,
`create_nursing`, `edit_nursing`, `complete_nursing`). Nurse receives full
CRUD; Doctor receives view-only access; Administrator keeps the development
override supplied by the service layer. The baseline files already represent
the table DDL, while the numbered migration preserves the ledgered release
boundary for existing installations.

The down migration removes the retained nursing assessment table and
permission seeds. It is destructive and restricted to an empty dedicated test
database or an explicitly approved recovery operation after the backup gate.

## Migration 025 â€” Phase 3.4 Laboratory

`025_phase3_laboratory_up.sql` creates `laboratory_requests` and
`laboratory_results`, then seeds six Laboratory permissions
(`view_laboratory`, `create_laboratory_request`, `process_laboratory_request`,
`enter_laboratory_result`, `edit_laboratory_result`,
`complete_laboratory_request`). Doctor receives clinical request access,
Nurse receives view-only access, Records Officer receives view-only access,
and Laboratory Scientist receives the full workflow permissions. The module
keeps requests and results as simple encounter-linked CRUD records with text
results and no catalogue, specimen, analyser, or PACS integration.

The down migration removes the Laboratory tables and permission seeds. It is
destructive and restricted to an empty dedicated test database or an
explicitly approved recovery operation after the backup gate.

## Migration 027 — Phase 3.5 Radiology

`027_phase3_radiology_up.sql` creates `radiology_requests` and
`radiology_reports`, then seeds six Radiology permissions
(`view_radiology`, `create_radiology_request`, `process_radiology_request`,
`enter_radiology_report`, `edit_radiology_report`,
`complete_radiology_request`). Doctor receives clinical request access,
Nurse receives view-only access, Records Officer receives view-only access,
and Radiographer receives the full workflow permissions. The module keeps
studies and reports as simple encounter-linked CRUD records with text-based
study, indication, findings, impression, and recommendation fields.

The down migration removes the Radiology tables and permission seeds. It is
destructive and restricted to an empty dedicated test database or an
explicitly approved recovery operation after the backup gate.
