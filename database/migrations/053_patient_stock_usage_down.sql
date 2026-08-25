-- Remove Patient Stock Usage structures.
-- Down migration is destructive to usage history and should only be used after backup review.

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN ('view_patient_stock_usage', 'record_patient_stock_usage');

DELETE FROM permissions
WHERE permission_key IN ('view_patient_stock_usage', 'record_patient_stock_usage');

DROP TABLE IF EXISTS patient_stock_usage;

ALTER TABLE stock_transactions
    MODIFY transaction_type ENUM('Receipt','Issue','Return','Adjustment') NOT NULL;
