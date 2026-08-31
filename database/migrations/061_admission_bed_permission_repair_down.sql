-- Roll back only the incremental admission/bed-assignment repair.
-- Original Migration 037/040 grants remain intact.

DELETE rp FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE (
        r.role_name = 'System Administrator'
        AND p.permission_key IN ('view_admissions', 'create_admission', 'transfer_admission')
    )
   OR (
        r.role_name IN ('Receptionist', 'Doctor')
        AND p.permission_key = 'transfer_admission'
    );

