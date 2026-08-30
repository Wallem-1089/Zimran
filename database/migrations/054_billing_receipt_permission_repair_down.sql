/*
|--------------------------------------------------------------------------
| Reverse Billing Receipt Permission Repair
|--------------------------------------------------------------------------
*/

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'Doctor',
    'Nurse',
    'Laboratory Scientist',
    'Radiographer',
    'Physiotherapist',
    'Theatre Staff',
    'Pharmacist',
    'Store Officer'
)
  AND p.permission_key = 'view_receipts';
