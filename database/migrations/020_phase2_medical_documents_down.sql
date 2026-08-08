/* Destructive rollback: empty isolated verification databases only. */
DELETE rp FROM role_permissions rp INNER JOIN permissions p ON p.id=rp.permission_id
WHERE p.permission_key IN ('view_medical_documents','upload_medical_documents','replace_medical_documents','archive_medical_documents','download_medical_documents','view_confidential_documents','view_document_history');
DELETE FROM permissions WHERE permission_key IN ('view_medical_documents','upload_medical_documents','replace_medical_documents','archive_medical_documents','download_medical_documents','view_confidential_documents','view_document_history');
DELETE FROM system_settings WHERE setting_key IN ('documents.allowed_types','documents.maximum_upload_bytes','documents.allowed_mime_types','documents.allowed_extensions','documents.confidentiality_levels','documents.default_confidentiality','documents.malware_scanning_required','documents.storage_provider','documents.download_cache_policy','documents.closed_encounter_uploads','documents.retention_years');
DROP TABLE medical_document_versions;
DROP TABLE medical_documents;
