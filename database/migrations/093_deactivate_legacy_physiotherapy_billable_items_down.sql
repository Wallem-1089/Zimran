-- Reactivate the legacy Physiotherapy billable item that existed before the
-- current approved two-item catalogue.

UPDATE billable_items bi
INNER JOIN departments d
    ON d.id = bi.department_id
SET bi.is_active = 1,
    bi.updated_at = NOW()
WHERE d.department_name = 'Physiotherapy'
  AND bi.item_code = 'P-SRV-00001';
