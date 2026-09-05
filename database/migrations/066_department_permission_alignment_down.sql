-- Revert ECG/POP alignment grants from Migration 066.

DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE r.role_name IN ('ECG Technician', 'POP Technician')
  AND p.permission_key IN (
    'view_encounter',
    'edit_encounter',
    'transfer_encounter',
    'receive_encounter',
    'change_encounter_status',
    'view_medical_record',
    'view_medical_documents',
    'upload_medical_documents',
    'download_medical_documents',
    'view_clinical_safety',
    'view_consultation',
    'view_vital_signs',
    'view_nursing',
    'view_laboratory',
    'view_radiology',
    'view_ecg',
    'view_pop',
    'view_physiotherapy',
    'view_theatre',
    'view_pharmacy',
    'view_problem_list',
    'view_medical_history',
    'view_billing',
    'create_billing_request',
    'view_billable_items',
    'view_inventory',
    'view_stock_requests',
    'create_stock_request',
    'cancel_stock_request',
    'view_patient_stock_usage',
    'record_patient_stock_usage',
    'view_reports',
    'view_clinical_reports',
    'process_ecg_request',
    'upload_ecg_chart',
    'edit_ecg_report',
    'complete_ecg_request',
    'process_pop_request',
    'record_pop_procedure',
    'edit_pop_record',
    'complete_pop_request'
  );
