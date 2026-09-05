-- Allow Pharmacy to record non-prescription patient stock usage from Pharmacy department stock.
-- Prescription dispensing remains handled by PharmacyService/StoreService dispensing flow.

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p ON p.permission_key = 'record_patient_stock_usage'
WHERE r.role_name = 'Pharmacist';
