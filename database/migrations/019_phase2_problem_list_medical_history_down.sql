/* Destructive rollback: empty isolated verification databases only. */
DELETE rp FROM role_permissions rp INNER JOIN permissions p ON p.id=rp.permission_id
WHERE p.permission_key IN ('view_problem_list','manage_problem_list','verify_problem_list','resolve_problem_list','view_medical_history','manage_medical_history','verify_medical_history','view_confidential_medical_history','view_problem_history');
DELETE FROM permissions WHERE permission_key IN ('view_problem_list','manage_problem_list','verify_problem_list','resolve_problem_list','view_medical_history','manage_medical_history','verify_medical_history','view_confidential_medical_history','view_problem_history');
DELETE FROM system_settings WHERE setting_key IN ('problem_list.categories','problem_list.severities','problem_list.allow_self_verification','problem_list.nurse_may_manage','problem_list.show_resolved_in_workspace','medical_history.types','medical_history.confidentiality_levels','medical_history.allow_self_verification');
DROP TABLE patient_medical_history_versions;
DROP TABLE patient_medical_history;
DROP TABLE patient_problem_history;
DROP TABLE patient_problems;
