DELETE FROM physiotherapy_patient_access;

DROP TABLE IF EXISTS physiotherapy_patient_access;

UPDATE billable_items
SET is_active = 0,
    updated_at = NOW()
WHERE item_code IN (
    'PHY-SRV-TREATMENT-ADULT',
    'PHY-SRV-TREATMENT-CHILD'
);

UPDATE billable_items bi
INNER JOIN departments d
    ON d.id = bi.department_id
SET bi.is_active = 1,
    bi.updated_at = NOW()
WHERE d.department_name = 'Physiotherapy'
  AND bi.item_code IN (
      'PHY-CON-FASTUM-GEL',
      'PHY-CON-SOFT-BALL'
  );
