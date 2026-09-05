DELETE rp
FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN ('manage_configurable_forms', 'view_configurable_form_responses');

DELETE FROM permissions
WHERE permission_key IN ('manage_configurable_forms', 'view_configurable_form_responses');

DROP TABLE IF EXISTS form_response_values;
DROP TABLE IF EXISTS form_responses;
DROP TABLE IF EXISTS form_fields;
DROP TABLE IF EXISTS form_definitions;
