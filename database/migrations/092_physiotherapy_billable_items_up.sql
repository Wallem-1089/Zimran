-- Add Physiotherapy billable items.

INSERT INTO billable_items (
    item_code,
    item_name,
    item_type,
    department_id,
    description,
    unit_price,
    unit,
    is_active,
    created_by,
    created_at,
    updated_at
)
SELECT seed.item_code,
       seed.item_name,
       seed.item_type,
       d.id,
       'Physiotherapy department billable item.',
       seed.unit_price,
       'Unit',
       1,
       u.id,
       NOW(),
       NOW()
FROM (
    SELECT 'PHY-CON-FASTUM-GEL' AS item_code, 'Fastum Gel' AS item_name, 'Consumable' AS item_type, 10000.00 AS unit_price
    UNION ALL SELECT 'PHY-CON-SOFT-BALL', 'Soft ball', 'Consumable', 4000.00
) seed
INNER JOIN departments d
    ON d.department_name = 'Physiotherapy'
INNER JOIN users u
    ON u.id = (
        SELECT u2.id
        FROM users u2
        ORDER BY CASE WHEN u2.username = 'walter' THEN 0 ELSE 1 END, u2.id
        LIMIT 1
    )
WHERE NOT EXISTS (
    SELECT 1
    FROM billable_items existing
    WHERE existing.item_code = seed.item_code
       OR (
           existing.department_id = d.id
           AND UPPER(TRIM(existing.item_name)) = UPPER(seed.item_name)
       )
);
