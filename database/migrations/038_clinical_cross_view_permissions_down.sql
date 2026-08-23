SET NAMES utf8mb4;

DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE (
    r.role_name = 'Laboratory Scientist'
    AND p.permission_key IN (
        'view_medical_record',
        'view_consultation',
        'view_vital_signs',
        'view_nursing',
        'view_radiology',
        'view_physiotherapy',
        'view_theatre',
        'view_pharmacy'
    )
)
OR (
    r.role_name = 'Radiographer'
    AND p.permission_key IN (
        'view_medical_record',
        'view_consultation',
        'view_vital_signs',
        'view_nursing',
        'view_laboratory',
        'view_physiotherapy',
        'view_theatre',
        'view_pharmacy'
    )
)
OR (
    r.role_name = 'Pharmacist'
    AND p.permission_key IN (
        'view_medical_record',
        'view_consultation',
        'view_vital_signs',
        'view_nursing',
        'view_laboratory',
        'view_radiology',
        'view_physiotherapy',
        'view_theatre'
    )
)
OR (
    r.role_name = 'Physiotherapist'
    AND p.permission_key IN (
        'view_medical_record',
        'view_consultation',
        'view_vital_signs',
        'view_nursing',
        'view_laboratory',
        'view_radiology',
        'view_theatre',
        'view_pharmacy'
    )
)
OR (
    r.role_name = 'Theatre Staff'
    AND p.permission_key IN (
        'view_medical_record',
        'view_consultation',
        'view_vital_signs',
        'view_nursing',
        'view_laboratory',
        'view_radiology',
        'view_physiotherapy',
        'view_pharmacy'
    )
)
OR (
    r.role_name = 'Nurse'
    AND p.permission_key = 'view_consultation'
)
OR (
    r.role_name = 'Records Officer'
    AND p.permission_key IN (
        'view_consultation',
        'view_nursing',
        'view_physiotherapy',
        'view_theatre'
    )
);
