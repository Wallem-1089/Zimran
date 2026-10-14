# Release Readiness

## 2026-10-12 hardening addendum

- Current hardening date: 2026-10-12
- Live database: `hospital_management_system`
- Dedicated test database used: `hms_test_hospital_management_system`
- Latest migration level: 097
  (`097_hardening_repair_staff_photo_and_catalogue_permissions_up.sql`)
- Live and test migration status: applied through 097 with no pending
  migrations reported.
- PHP syntax validation: 684 PHP files checked, 0 failures.
- Broad selected regression suite: passed.
- POST/CSRF scan: 189 candidate files, 0 without CSRF marker.
- Suspicious GET state-route scan: 6 candidates reviewed; they are POST-only
  handlers with CSRF or read-only confirmation/form pages.
- Targeted integrity checks returned zero failures for visit/patient mismatch,
  negative department stock, negative invoice balance, orphan registration
  billing requests, and duplicate active hospital numbers.

New/current-scope workflows included in this pass:

- Family Card registration, active Family Card membership, and Family Card
  renewal billing.
- Legacy/Existing Hospital Patient import with no initial registration bill,
  old card-number/proof capture, immediate system hospital number, and 30 days
  validity.
- Emergency registration/encounter behavior with emergency billing override.
- Request-based treatment relationship for request-only departments, including
  Emergency encounters.
- Pharmacy-owned price catalogue permissions.
- Store/Pharmacy stock split and Pharmacy drug stock request handling.
- Staff profile photo storage and protected preview.
- Multi-file diagnostic attachment and WhatsApp handoff wiring.

Fixes made during this hardening pass:

- Added Migration 097 to repair missing `staff_profiles.profile_photo_path` in
  drifted environments and remove stale Accounts billable-item mutation grants.
- Hardened `PermissionService` so active request ownership establishes
  treatment relationship for request departments.
- Hardened Laboratory request processing so Laboratory users can process
  Laboratory requests sent to them without encounter transfer ownership.
- Updated regressions for the current default Doctor initial encounter
  workflow, unique registration test data, active billable-item fixtures, and
  explicit invoice creation/refresh after auto-charge.

Final automated result for this pass: READY FOR CURRENT-SCOPE HANDOFF based on
syntax, migration, permission, request-billing, registration, emergency,
department, stock/pharmacy, staff-profile, and broad regression checks.

## Release baseline

- Application: Zimran E-HMIS / Hospital Management System
- Application config version: `1.2`
- Legacy constant version: `APP_VERSION = 1.2`
- Release hardening date: 2026-10-12
- PHP tested locally: 8.2.12
- Database engine in final backup header: MariaDB 10.4.32
- Live database: `hospital_management_system`
- Dedicated test database used: `hms_test_hospital_management_system`
- Latest migration level: 097 (`097_hardening_repair_staff_photo_and_catalogue_permissions_up.sql`)
- Migration status: no pending migrations and no checksum mismatches reported for the dedicated test database or live database.

## Implemented modules in scope

Authentication/session management; Administration/users/roles/permission matrix/settings; staff profiles with protected profile photos; patient registration/search/demographics/history/communications; payment-based registration, monthly renewal, emergency registration billing, Family Card registration/renewal, and Legacy Patient import; encounters/visits/queues/transfers/workspace; request-based departmental workflow; multi-billable clinical requests; Accounts billing clearance gate; Emergency Department and emergency reports; Patient Chart; Medical Records; MPI; Clinical Safety; Allergies; Alerts; Problem List; Medical History; Medical Documents; Clinical Notes; Department and User Notifications; Consultation; Vital Signs; Nursing; Laboratory; Radiology/X-Ray; ECG; Plaster with legacy POP internals; Physiotherapy; Theatre; Admissions/wards/beds; Accounts billing queues; Pharmacy-owned Price Catalogue; Store inventory/stock intake/barcodes/external sales; Store-to-Pharmacy stock transfer; Pharmacy onward stock movement and ledger; Stock Requests; Patient Stock Usage; Pharmacy prescriptions/dispensing; Billing/charges/invoices/discounts/payments/receipts; Reports/dashboards; Configurable Form Fields; diagnostic attachments and WhatsApp selectable attachment handoff.

## Ownership boundaries

- Pharmacy owns price catalogue setup for billable stock item pricing.
- Store owns hospital stock intake, Central Store balance, full stock ledger, and external Store sales.
- Store only transfers stock to Pharmacy.
- Pharmacy owns onward departmental stock movement, prescriptions, dispensing, and Pharmacy stock ledger.
- Billing owns patient charges, invoices, discounts, payments, receipts, registration billing, renewal billing, and emergency registration billing.
- Accounts owns billing review/clearance workflows, not price catalogue setup.
- Medical Records can perform Receptionist registration/encounter actions where explicitly authorized.
- Encounter lifecycle and request ownership remain server-side authorization gates.

## Final backup

- Final release backup: `database/backups/hms_final_release_20261001_213802.sql`
- Size: 1,267,110 bytes
- Verification:
  - file exists
  - non-zero size
  - recognized MariaDB dump header
  - database in dump header: `hospital_management_system`

Earlier backups remain preserved in `database/backups/`.

## Migration status

- Latest migration file present: `097_hardening_repair_staff_photo_and_catalogue_permissions_up.sql`
- Test database migration status: no `Pending` and no `Checksum mismatch`
- Live database migration status: no `Pending` and no `Checksum mismatch`
- Migration 097 was applied during the 2026-10-12 hardening pass.
- `database/hospital.sql` was synchronized to the canonical `database/schema.sql` baseline.
- `database/migrations/README.md` was updated to document migrations 072-097.

## Tests executed

The broad non-destructive regression suite was run against the dedicated test database only:

- `accounts_dashboard_queues_test.php`
- `audit_test.php`
- `clinical_cross_view_permission_test.php`
- `configurable_form_fields_test.php`
- `current_scope_permission_matrix_test.php`
- `database_test.php`
- `diagnostic_attachments_static_test.php`
- `helpers_test.php`
- `layout_test.php`
- `patient_registration_billing_test.php`
- `pharmacy_store_workflow_static_test.php`
- `phase_ecg_test.php`
- `phase_inpatient_admissions_test.php`
- `phase_pop_test.php`
- `phase1_8_remediation_test.php`
- `phase1_regression_test.php`
- `phase2_clinical_notes_test.php`
- `phase2_clinical_safety_hardening_test.php`
- `phase2_clinical_safety_test.php`
- `phase2_medical_documents_test.php`
- `phase2_medical_records_foundation_test.php`
- `phase2_mpi_test.php`
- `phase2_problem_list_medical_history_test.php`
- `phase3_consultation_notifications_test.php`
- `phase3_laboratory_test.php`
- `phase3_nursing_test.php`
- `phase3_physiotherapy_test.php`
- `phase3_radiology_test.php`
- `phase3_theatre_test.php`
- `phase3_vital_signs_test.php`
- `phase4_accounts_test.php`
- `phase4_billing_test.php`
- `phase4_pharmacy_test.php`
- `phase4_reports_test.php`
- `phase4_store_test.php`
- `session_test.php`
- `settings_test.php`
- `sidebar_visibility_test.php`
- `staff_profile_test.php`
- `superadmin_admin_split_test.php`
- `user_permission_overrides_test.php`

Excluded from the bulk run: the explicit migration down/up cycle tests for 019, 020, and 021, because those tests require special empty-table/destructive-test preconditions.

## Test results

- Broad non-destructive regression suite: passed.
- PHP syntax validation: 684 source PHP files checked, 0 failures.
- POST/CSRF scan: 189 candidate files found; 0 without CSRF marker.
- Suspicious GET state-changing route scan: 6 candidates reviewed; no unsafe
  GET mutation found.
- Migration status checks: passed.
- Final backup verification: passed.

## Issues fixed during this hardening pass

### BLOCKER

- None found.

### HIGH

- None found in the current hardening run.

### MEDIUM

- Permission matrix fallback did not include Doctor clinical Physiotherapy referral access when synthetic/matrix users were used outside seeded DB role rows. Fixed in `PermissionService`.
- Added `current_scope_permission_matrix_test.php` to cover the current workflow boundaries.

### LOW / housekeeping

- `config/constants.php` version was aligned from `1.1` to `1.2`.
- `database/hospital.sql` was synchronized with the canonical schema baseline.
- Migration README documentation was extended for migrations 072-080.

## Database integrity result

Targeted read-only checks on the dedicated test database returned zero for:

- invalid visit-patient relationships
- duplicate active admissions per visit
- negative department stock balances
- invoice overpayment
- negative invoice balances
- negative invoice totals
- charge amount mismatches
- billing requests with invalid visit/patient ownership
- registration billing requests without patients
- diagnostic attachments without valid patient/visit ownership
- duplicate active hospital numbers
- Lab/Radiology/ECG/Plaster/Physiotherapy/Theatre/Prescription patient-visit mismatches
- Legacy/Family Card registration migrations present and registration billing
  request patient ownership valid

## Security and authorization verification

- Server-side permission checks remain authoritative.
- Hidden UI buttons are not relied on as authorization.
- Completed/Cancelled encounter mutation protection remains in regression coverage.
- Emergency reports require administrator access or explicit `view_emergency_reports`.
- Request departments can process their own request queues without encounter transfer ownership.
- Super Administrator keeps override access.
- System Administrator keeps Administration/cross-worklist access without inheriting normal clinical mutation access.
- User-level permission overrides remain functional.

## Inventory / Pharmacy / Billing result

- Store receives stock and transfers stock only to Pharmacy.
- Pharmacy can issue stock onward to departments and owns the Pharmacy stock ledger.
- Store retains full ledger and external sales.
- Inventory category is restricted to Drug or Consumable.
- Barcode/SKU wiring is present and regression-covered.
- Pharmacy owns price catalogue mutation.
- Accounts owns billing review/clearance and payment handling.
- Billing/registration billing regressions pass.
- Multi-billable clinical request billing is regression-covered.
- Price changes preserve existing charge snapshots.

## Encounter workflow result

- Reception/Records registration and encounter creation are covered.
- Doctor clinical request creation is covered for request departments.
- Department request worklists remain request-owned.
- Billing clearance gate remains enforced before normal request processing.
- Emergency override path remains covered.
- Completed/Cancelled encounters remain protected from unauthorized normal CRUD.

## Known non-blocking limitations

- `layout_test.php`, `helpers_test.php`, `database_test.php`, and `session_test.php` print demonstration HTML/text rather than concise PASS lines; they completed successfully.
- The explicit migration cycle tests were not part of the normal bulk run because they require special destructive-test preconditions.

## Deployment checklist

- Copy the final backup off-host before production handoff.
- Confirm writable paths for logs, document storage, diagnostic attachments, and backups.
- Confirm PHP upload limits match expected diagnostic attachment sizes.
- Confirm production environment variables and database credentials are set outside source control.
- Confirm web server does not expose secure document/attachment storage directly.
- Confirm Super Administrator credentials and permission matrix are reviewed.
- Confirm scheduled/operational backups are configured outside this repository.

## Rollback / restore reference

- Use `database/backups/hms_final_release_20261001_213802.sql` as the current final release backup.
- Earlier release backups remain available in `database/backups/`.

## Final release status

READY FOR RELEASE based on the automated regression, syntax, migration, backup, CSRF, authorization, and targeted integrity checks completed in this hardening pass.
