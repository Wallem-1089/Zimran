/* Phase 3.2 rollback: removes Vital Signs tables and permissions. */

DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN ('view_vital_signs', 'create_vital_signs', 'edit_vital_signs');

DELETE FROM permissions
WHERE permission_key IN ('view_vital_signs', 'create_vital_signs', 'edit_vital_signs');

DROP TABLE IF EXISTS vital_signs;
