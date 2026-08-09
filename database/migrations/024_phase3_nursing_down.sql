DROP TABLE IF EXISTS nursing_assessments;

DELETE FROM role_permissions
WHERE permission_id IN (
    SELECT id FROM permissions
    WHERE permission_key IN ('view_nursing', 'create_nursing', 'edit_nursing', 'complete_nursing')
);

DELETE FROM permissions
WHERE permission_key IN ('view_nursing', 'create_nursing', 'edit_nursing', 'complete_nursing');
