DELETE rp FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_reports',
    'view_financial_reports',
    'view_inventory_reports',
    'view_clinical_reports'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_reports',
    'view_financial_reports',
    'view_inventory_reports',
    'view_clinical_reports'
);
