/*
|--------------------------------------------------------------------------
| Reverse Billing Permission Seed Repair
|--------------------------------------------------------------------------
|
| Removes role grants added by migration 055. Permission rows are retained
| because deleting permissions can break existing audit/admin references.
|
*/

DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_billing',
    'view_receipts',
    'create_patient_charge',
    'cancel_patient_charge',
    'create_invoice',
    'record_payment'
)
  AND r.role_name IN (
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
  );
