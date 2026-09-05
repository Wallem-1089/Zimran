-- Remove Pharmacy patient stock usage recording grant from Migration 067.

DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE r.role_name = 'Pharmacist'
  AND p.permission_key = 'record_patient_stock_usage';
