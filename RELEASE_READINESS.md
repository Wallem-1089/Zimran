# Release Readiness

## Release

- Application: Zimran E-HMIS
- Version: 1.2
- Release review date: 2026-09-25
- PHP: 8.2.12 tested locally
- Database: MariaDB 10.4.32 tested locally
- Live database: `hospital_management_system`
- Dedicated test database: `hms_test_hospital_management_system`
- Migration level: 072 (`072_billing_discounts_up.sql`)
- Migration integrity: 71 migration files, 71 ledger entries, no pending migrations, no checksum mismatches.

## Implemented modules

Authentication, Administration/User Management, Patients, Reception/Encounters,
Patient Search, Medical Records, MPI/Patient Identity, Clinical Safety, Problem
List, Medical History, Medical Documents, Clinical Notes, Department
Notifications, User Notifications, Consultation, Vital Signs, Nursing,
Laboratory, Radiology/X-Ray, ECG, POP/Casting, Physiotherapy, Theatre,
Admissions/Wards/Beds, Accounts/Price Catalogue, Store/Inventory, Stock
Requests, Patient Stock Usage, Pharmacy, Billing, Dashboards/Reports,
Configurable Form Fields, Patient Communications/WhatsApp handoff.

## Ownership boundaries

- Accounts owns PRICE.
- Store owns STOCK.
- Pharmacy owns PRESCRIPTIONS and DISPENSING.
- Billing owns PATIENT CHARGES, INVOICES, DISCOUNTS, PAYMENTS, and RECEIPTS.

## Final backup

- Final release backup:
  `database/backups/hms_final_release_20260925_145513.sql`
- Verification:
  - file exists
  - non-zero size
  - recognized MariaDB dump header
  - database name: `hospital_management_system`

## Security controls verified

- Authentication and logout routes present.
- Session configuration uses cookie-only, strict-mode sessions.
- State-changing route sweep: 164 route files scanned; no possible CSRF misses found.
- PermissionService is the server-side authorization authority; hidden buttons are not relied on as authorization.
- Universal patient search does not itself grant confidential clinical record mutation.
- Medical Document access remains permission-checked through controller routes.
- Financial totals and stock balances are controlled through services, not direct manual edits.

## Database/integrity checks

Read-only live integrity checks returned zero for:

- negative department stock
- negative invoice values/balances
- payment greater than invoice total on non-cancelled invoices
- active charge amount mismatch
- orphan active admission bed link
- duplicate active admissions per visit

No live destructive database operation was performed during this review.

## Tests executed

Automated tests were run with:

```powershell
HMS_APP_ENV=testing
HMS_TEST_DB_NAME=hms_test_hospital_management_system
```

Executed suites included:

- database/session/helpers/layout/settings/audit
- user permission overrides
- sidebar visibility
- Super Administrator/Admin split
- Phase 1 core patient/encounter workflow
- Medical Records foundation, MPI, Clinical Safety, Medical Documents, Clinical Notes, Problem List/Medical History
- Consultation and Department Notifications
- Vital Signs
- Nursing
- Laboratory
- Radiology/X-Ray
- Physiotherapy
- Theatre
- Accounts
- Store/Inventory
- Pharmacy
- Billing, including charge-specific and invoice-level discounts
- Reports/Dashboards
- Inpatient Admissions/Wards/Beds
- ECG
- POP/Casting
- Configurable Form Fields
- Clinical cross-view permissions

Result: all selected automated suites passed after the admissions test fixture
was corrected to avoid mixing a Super Administrator database id with a
System Administrator role override.

PHP syntax validation:

- 656 PHP files checked
- 0 syntax failures

## Issues found/fixed

### HIGH

- Inpatient admissions regression failed because the test simulated a System
  Administrator using Walter's Super Administrator database id. The test
  fixture was corrected to use a role/department-only System Administrator
  subject. Production authorization logic was not weakened.

### MEDIUM

- Billing discount behavior was improved before this release readiness pass:
  discounts can now target a specific charge line or the whole invoice.
  Calculation order is gross charges, minus line discounts, minus invoice-level
  discounts, minus payments.

## Environment requirements

- PHP 8+
- MySQL/MariaDB
- PDO MySQL extension
- `mysqldump` available for backups, or `HMS_MYSQLDUMP_PATH` configured
- Secure document storage outside the public web root
- Writable project paths:
  - `database/backups/`
  - `storage/logs/`
  - `storage/sessions/`
  - secure document storage root

Recommended production environment variables:

```ini
HMS_APP_ENV=production
HMS_BASE_URL=/
HMS_DB_HOST=localhost
HMS_DB_NAME=your_virtualmin_database
HMS_DB_USER=your_virtualmin_database_user
HMS_DB_PASS=your_virtualmin_database_password
HMS_DOCUMENT_STORAGE_ROOT=/home/zimran/hms_secure_documents
HMS_PHP_ERROR_LOG=/home/zimran/logs/hms_php_errors.log
```

## Deployment checklist

- Import the verified database backup only into the intended target database.
- Configure environment database credentials.
- Confirm `HMS_BASE_URL` matches deployment path.
- Confirm secure document storage path exists and is writable by PHP.
- Confirm `display_errors=Off` in production.
- Confirm PHP error log path is writable.
- Confirm HTTPS/session cookie behavior on production domain.
- Run a browser smoke test for login, dashboard, patient search, patient chart,
  encounter workspace, clinical tabs, Accounts, Store, Pharmacy, Billing,
  Reports, and logout.

## Rollback/restore reference

- Keep the final release backup and prior migration backups.
- Restore only into a separate restore-test database first.
- Do not restore directly over live without a verified recovery plan and fresh
  current backup.

## Known non-blocking limitations

- No insurance/HMO/discount approval chain beyond controlled Billing discounts.
- No refund/overpayment workflow yet.
- No advanced BI/report designer/export engine.
- No PACS/DICOM imaging integration.
- No pharmacy batch/expiry/partial refill workflow.
- WhatsApp handoff is manual and logged; it does not use WhatsApp Cloud API.

## Final status

READY FOR RELEASE, subject to the deployment checklist and a final browser smoke
test in the target environment.
