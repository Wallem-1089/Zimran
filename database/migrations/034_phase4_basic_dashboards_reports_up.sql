INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_reports', 'View Reports', 'Reports', 'View the basic reports module.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_reports');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_financial_reports', 'View Financial Reports', 'Reports', 'View Billing financial summaries.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_financial_reports');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_inventory_reports', 'View Inventory Reports', 'Reports', 'View Store inventory summaries.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_inventory_reports');

INSERT INTO permissions (permission_key, permission_name, module, description, is_active)
SELECT 'view_clinical_reports', 'View Clinical Reports', 'Reports', 'View aggregate clinical activity summaries.', 1
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE permission_key = 'view_clinical_reports');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'System Administrator',
    'Accounts',
    'Accountant',
    'Store Officer',
    'Doctor',
    'Nurse',
    'Laboratory Scientist',
    'Radiographer',
    'Physiotherapist',
    'Theatre Staff',
    'Pharmacist',
    'Records Officer'
)
  AND p.permission_key = 'view_reports';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('System Administrator', 'Accounts', 'Accountant')
  AND p.permission_key = 'view_financial_reports';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN ('System Administrator', 'Store Officer')
  AND p.permission_key = 'view_inventory_reports';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
INNER JOIN permissions p
WHERE r.role_name IN (
    'System Administrator',
    'Doctor',
    'Nurse',
    'Laboratory Scientist',
    'Radiographer',
    'Physiotherapist',
    'Theatre Staff',
    'Pharmacist',
    'Records Officer'
)
  AND p.permission_key = 'view_clinical_reports';
