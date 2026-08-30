/*
|--------------------------------------------------------------------------
| Billing Permission Seed Repair
|--------------------------------------------------------------------------
|
| Ensure the core Billing / Patient Accounts permissions exist after earlier
| live reconciliation drift, and grant them according to the current
| permission boundary:
|
| - broad view_billing for encounter charge visibility
| - receipt visibility only for Accounts/Reception/Records/Admin-facing roles
| - financial mutation only for Accounts/Accountant
|
*/

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_billing', 'View Billing', 'Billing', 'View patient billing, charges, invoices, and balances.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_billing');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_patient_charge', 'Create Patient Charge', 'Billing', 'Create patient charges from billable items.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_patient_charge');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'cancel_patient_charge', 'Cancel Patient Charge', 'Billing', 'Cancel patient charges where allowed.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'cancel_patient_charge');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'create_invoice', 'Create Invoice', 'Billing', 'Create and refresh patient invoices.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'create_invoice');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'record_payment', 'Record Payment', 'Billing', 'Record patient payments.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'record_payment');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_receipts', 'View Receipts', 'Billing', 'View and print payment receipts.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_receipts');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'System Administrator',
    'Accounts',
    'Accountant',
    'Receptionist',
    'Records Officer',
    'Doctor',
    'Nurse',
    'Laboratory Scientist',
    'Radiographer',
    'Physiotherapist',
    'Theatre Staff',
    'Pharmacist',
    'Store Officer'
)
  AND p.permission_key = 'view_billing';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'System Administrator',
    'Accounts',
    'Accountant',
    'Receptionist',
    'Records Officer'
)
  AND p.permission_key = 'view_receipts';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'System Administrator',
    'Accounts',
    'Accountant'
)
  AND p.permission_key IN (
      'create_patient_charge',
      'cancel_patient_charge',
      'create_invoice',
      'record_payment'
  );
