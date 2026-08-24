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
25. `026_phase3_laboratory_result_details_up.sql`
26. `027_phase3_radiology_up.sql`
27. `028_phase3_physiotherapy_up.sql`
28. `029_phase3_theatre_up.sql`
29. `030_phase4_accounts_price_catalogue_up.sql`
30. `031_phase4_store_inventory_up.sql`

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

## Migration 028 â€” Phase 3.6 Physiotherapy

`028_phase3_physiotherapy_up.sql` creates `physiotherapy_records` and
`physiotherapy_sessions`, then seeds five Physiotherapy permissions
(`view_physiotherapy`, `create_physiotherapy`, `edit_physiotherapy`,
`manage_physiotherapy_sessions`, `complete_physiotherapy`). Doctor receives
clinical referral access, Nurse receives view-only access, and Physiotherapist
receives the full workflow permissions. The module keeps the record simple:
one primary physiotherapy record per visit plus multiple append-only session
rows.

The down migration removes the Physiotherapy tables and permission seeds. It
is destructive and restricted to an empty dedicated test database or an
explicitly approved recovery operation after the backup gate.

## Migration 029 â€” Phase 3.7 Theatre

`029_phase3_theatre_up.sql` creates a single `theatre_records` table and
seeds the four Theatre permissions (`view_theatre`, `create_theatre`,
`edit_theatre`, `complete_theatre`). Doctor, Theatre Staff, and Nurse receive
their respective access levels; Theatre Staff and Doctor can create and
complete while Nurse is view-only. The module keeps theatre as a single
encounter-linked draft/completed clinical record with mostly text fields and
no separate request, scheduling, anaesthesia, recovery, or checklist tables.

The down migration removes the Theatre table and permission seeds. It is
destructive and restricted to an empty dedicated test database or an
explicitly approved recovery operation after the backup gate.

## Migration 030 â€” Phase 4.1 Accounts / Price Catalogue

`030_phase4_accounts_price_catalogue_up.sql` creates the standalone
`billable_items` price catalogue and seeds the four Accounts permissions
(`view_billable_items`, `create_billable_items`, `edit_billable_items`,
`manage_billable_item_status`). Accountant receives full catalogue CRUD.
Doctor, Nurse, Laboratory Scientist, Radiographer, Physiotherapist, Theatre
Staff, Pharmacist, Receptionist, Records Officer, and Store Officer receive
view-only access so the current price catalogue can be consulted where
needed. The module is sidebar-owned, not an Encounter Workspace tab, and it
does not create patient charges, invoices, payments, or receipts.

The down migration removes the price-catalogue table and permission seeds. It
is destructive and restricted to an empty dedicated test database or an
explicitly approved recovery operation after the backup gate.

## Migration 032 — Phase 4.3 Pharmacy / Dispensing

`032_phase4_pharmacy_up.sql` creates the encounter-linked `prescriptions`
and `pharmacy_dispensing` tables and seeds the Pharmacy permissions
(`view_pharmacy`, `create_prescription`, `edit_prescription`,
`dispense_prescription`). Pharmacist receives the full workflow, Doctor can
create and edit clinical prescriptions, and Nurse and Records Officer receive
view-only access. The module keeps Pharmacy as a small prescription and
dispensing layer that reuses Store inventory balances for stock consumption
and does not create patient charges.

The down migration removes the Pharmacy tables and permission seeds. It is
destructive and restricted to an empty dedicated test database or an
explicitly approved recovery operation after the backup gate.

## Migration 034 - Phase 4.5 Basic Dashboards / Reports

`034_phase4_basic_dashboards_reports_up.sql` seeds the read-only Reports
permissions (`view_reports`, `view_financial_reports`,
`view_inventory_reports`, `view_clinical_reports`). No reporting tables are
created. Reports are generated from existing operational tables through
bounded aggregate queries in `DashboardService`.

The down migration removes only the Reports permission seeds and role grants.

## Migration 035 - Patient Registration Demographics

`035_patient_registration_demographics_up.sql` additively extends patient
registration demographics with place of work, ethnic group, religion, and next
of kin address. Existing other-name handling continues to use the established
`middle_name` field for backward compatibility.

The down migration removes only these four optional demographic columns and
should be reviewed before execution on any database containing registered
patients.

## Migration 036 - Encounter Completion / Discharge Details

`036_encounter_completion_discharge_up.sql` additively extends `visits` with
encounter completion attribution and discharge fields: `completed_at`,
`completed_by`, `discharge_diagnosis`, `discharge_notes`, and
`follow_up_instructions`.

The down migration removes only these optional completion/discharge columns and
indexes. Review carefully before use on a database with completed encounters.

## Migration 037 - Inpatient Admissions / Ward & Bed Workflow

`037_inpatient_admissions_up.sql` creates the practical inpatient structures:
`wards`, `ward_beds`, `admissions`, and `admission_movements`. Admissions are
linked to an existing encounter, occupy exactly one active bed, support ward/bed
transfer, and release the bed on discharge or cancellation. The movement table
records admission, transfer, discharge, and cancellation events.

The migration seeds `view_admissions`, `create_admission`,
`transfer_admission`, `discharge_admission`, and `manage_wards_beds`.
Administrator retains full access through the normal permission override.
Reception, Records, Doctor, and Nursing roles receive practical access levels.

## Migration 038 - Clinical Cross-View Permissions

`038_clinical_cross_view_permissions_up.sql` aligns role grants with the
encounter-centered clinical viewing policy. Doctor, Nurse, Laboratory
Scientist, Radiographer, Physiotherapist, Theatre Staff, Pharmacist, and
Records Officer receive view-only access across the core clinical context
permissions: Medical Records, Consultation, Vital Signs, Nursing, Laboratory,
Radiology, Physiotherapy, Theatre, and Pharmacy.

The migration does not grant additional create, edit, complete, dispense, or
financial permissions. Department-owned mutation rules remain unchanged; Vital
Signs mutation remains limited to Doctor, Nurse, and Administrator.

The down migration removes only the added cross-view grants and preserves each
role's original department-owned mutation permissions.

## Migration 039 - Security Lockout Threshold

`039_security_lockout_threshold_10_up.sql` updates the existing
`security.lockout_threshold` setting from 5 to 10 failed login attempts and
keeps the validation range at 1-20. The application fallback in `config/app.php`
also uses 10.

The down migration restores the original Phase 1 default of 5 attempts.

## Migration 040 - Receptionist Admission Permission Repair

`040_receptionist_admission_permission_repair_up.sql` idempotently ensures the
Receptionist role has `view_admissions` and `create_admission`. This matches
the intended inpatient admission policy from Migration 037 and repairs any live
permission ledger drift.

The down migration is intentionally a no-op so it does not remove the baseline
Migration 037 admission grants.

## Migration 041 - User Notifications

`041_user_notifications_up.sql` creates `user_notifications` for direct
staff-to-staff encounter attention requests. These notifications link to an
existing patient and visit, target one user account, and support
Unread/Read/Resolved statuses. They do not transfer encounter ownership, alter
queues, or replace Department Notifications.

The down migration drops only the `user_notifications` table and should be
reviewed before use on databases containing notification history.

## Migration 044 - Billing Requests / Charge Recommendations

`044_billing_requests_up.sql` creates `billing_requests`, a non-financial queue
where department users can recommend charges for Accounts review. Pending
requests link to the encounter, patient, requesting department/user, optional
source module/record, optional suggested Accounts billable item, and quantity.

Accounts/Admin can convert a pending request into an official `patient_charges`
row through `BillingService`; the request is then marked `Charged` and linked
to the charge. Pending or cancelled requests do not affect invoices, balances,
payments, or receipts.

The migration seeds `create_billing_request`, `view_billing_requests`,
`review_billing_request`, and `cancel_billing_request`.
