/*
|--------------------------------------------------------------------------
| Billing Receipt Permission Repair
|--------------------------------------------------------------------------
|
| Keep broad encounter billing visibility separate from receipt visibility.
| Receipts should remain Accounts/Reception/Records/Admin-facing by default.
|
*/

DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key = 'view_receipts'
  AND r.role_name NOT IN (
      'System Administrator',
      'Accounts',
      'Accountant',
      'Receptionist',
      'Records Officer'
  );
