-- Deactivate legacy Physiotherapy billable items outside the current approved
-- Physiotherapy catalogue.

UPDATE billable_items bi
INNER JOIN departments d
    ON d.id = bi.department_id
SET bi.is_active = 0,
    bi.updated_at = NOW()
WHERE d.department_name = 'Physiotherapy'
  AND bi.item_code NOT IN (
      'PHY-CON-FASTUM-GEL',
      'PHY-CON-SOFT-BALL'
  );
