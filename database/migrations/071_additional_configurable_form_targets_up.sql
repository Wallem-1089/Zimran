INSERT INTO form_definitions (form_key, form_name, description, is_active)
SELECT seed.form_key, seed.form_name, seed.description, 1
FROM (
    SELECT 'theatre_record' AS form_key, 'Theatre Record' AS form_name, 'Optional extra Theatre operation-note fields.' AS description
    UNION ALL SELECT 'admission_record', 'Admission Record', 'Optional extra Admission fields.'
    UNION ALL SELECT 'dressing_record', 'Dressing Record', 'Optional extra Dressing Book follow-up fields.'
    UNION ALL SELECT 'dm_sheet', 'DM Sheet', 'Optional extra diabetes monitoring fields.'
    UNION ALL SELECT 'ecg_report', 'ECG Report', 'Optional extra ECG chart/note fields.'
    UNION ALL SELECT 'pop_record', 'POP Procedure Record', 'Optional extra POP/casting procedure fields.'
    UNION ALL SELECT 'physiotherapy_record', 'Physiotherapy Record', 'Optional extra Physiotherapy assessment fields.'
) seed
WHERE NOT EXISTS (
    SELECT 1 FROM form_definitions fd WHERE fd.form_key = seed.form_key
);

INSERT INTO form_fields (
    form_definition_id, field_key, field_label, field_type, is_required, sort_order, is_active
)
SELECT fd.id, seed.field_key, seed.field_label, seed.field_type, 0, seed.sort_order, 0
FROM form_definitions fd
INNER JOIN (
    SELECT 'theatre_record' AS form_key, 'operation_checklist_notes' AS field_key, 'Operation Checklist Notes' AS field_label, 'textarea' AS field_type, 10 AS sort_order
    UNION ALL SELECT 'theatre_record', 'patient_positioning_notes', 'Patient Positioning Notes', 'textarea', 20
    UNION ALL SELECT 'theatre_record', 'instrument_count_notes', 'Instrument Count Notes', 'textarea', 30
    UNION ALL SELECT 'admission_record', 'special_nursing_instructions', 'Special Nursing Instructions', 'textarea', 10
    UNION ALL SELECT 'admission_record', 'dietary_notes', 'Dietary Notes', 'textarea', 20
    UNION ALL SELECT 'admission_record', 'relative_contact_notes', 'Relative Contact Notes', 'textarea', 30
    UNION ALL SELECT 'dressing_record', 'dressing_checklist', 'Dressing Checklist', 'textarea', 10
    UNION ALL SELECT 'dressing_record', 'patient_education_given', 'Patient Education Given', 'textarea', 20
    UNION ALL SELECT 'dressing_record', 'wound_photo_reference', 'Wound Photo Reference', 'text', 30
    UNION ALL SELECT 'dm_sheet', 'diet_advice_given', 'Diet Advice Given', 'textarea', 10
    UNION ALL SELECT 'dm_sheet', 'hypoglycaemia_warning_signs', 'Hypoglycaemia Warning Signs', 'textarea', 20
    UNION ALL SELECT 'ecg_report', 'quality_notes', 'ECG Quality Notes', 'textarea', 10
    UNION ALL SELECT 'ecg_report', 'patient_preparation_notes', 'Patient Preparation Notes', 'textarea', 20
    UNION ALL SELECT 'pop_record', 'neurovascular_check', 'Neurovascular Check', 'textarea', 10
    UNION ALL SELECT 'pop_record', 'cast_care_education', 'Cast Care Education', 'textarea', 20
    UNION ALL SELECT 'physiotherapy_record', 'mobility_aid_required', 'Mobility Aid Required', 'text', 10
    UNION ALL SELECT 'physiotherapy_record', 'home_exercise_advice', 'Home Exercise Advice', 'textarea', 20
) seed ON seed.form_key = fd.form_key
WHERE NOT EXISTS (
    SELECT 1
    FROM form_fields ff
    WHERE ff.form_definition_id = fd.id
      AND ff.field_key = seed.field_key
);
