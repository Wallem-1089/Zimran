DELETE frv
FROM form_response_values frv
INNER JOIN form_fields ff ON ff.id = frv.form_field_id
INNER JOIN form_definitions fd ON fd.id = ff.form_definition_id
WHERE fd.form_key IN (
    'theatre_record',
    'admission_record',
    'dressing_record',
    'dm_sheet',
    'ecg_report',
    'pop_record',
    'physiotherapy_record'
);

DELETE fr
FROM form_responses fr
WHERE fr.form_key IN (
    'theatre_record',
    'admission_record',
    'dressing_record',
    'dm_sheet',
    'ecg_report',
    'pop_record',
    'physiotherapy_record'
);

DELETE ff
FROM form_fields ff
INNER JOIN form_definitions fd ON fd.id = ff.form_definition_id
WHERE fd.form_key IN (
    'theatre_record',
    'admission_record',
    'dressing_record',
    'dm_sheet',
    'ecg_report',
    'pop_record',
    'physiotherapy_record'
);

DELETE FROM form_definitions
WHERE form_key IN (
    'theatre_record',
    'admission_record',
    'dressing_record',
    'dm_sheet',
    'ecg_report',
    'pop_record',
    'physiotherapy_record'
);
