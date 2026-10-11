-- Seed global billable catalogue items available across request/billing workflows.
-- department_id is intentionally NULL so department-specific dropdowns include
-- these items alongside each department's own catalogue.

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
       NULL,
       'Global billable item available to necessary departments.',
       seed.unit_price,
       'Unit',
       1,
       u.id,
       NOW(),
       NOW()
FROM (
    SELECT 'GEN-CON-ANKLE-SHOULDER-SUP' AS item_code, 'ANKLE SUPPORT / SHOULDER SUPPORT' AS item_name, 'Consumable' AS item_type, 25000.00 AS unit_price
    UNION ALL SELECT 'GEN-CON-COMPRESSOR-SOCKS', 'COMPRESSOR SOCKS', 'Consumable', 40000.00
    UNION ALL SELECT 'GEN-CON-KNEE-BRACE', 'KNEE BRACE', 'Consumable', 40000.00
    UNION ALL SELECT 'GEN-CON-LONG-KNEE-BRACE', 'LONG KNEE BRACE', 'Consumable', 60000.00
    UNION ALL SELECT 'GEN-CON-NECK-COLLAR', 'NECK COLLAR', 'Consumable', 30000.00
    UNION ALL SELECT 'GEN-CON-FOLDABLE-COMMODE', 'FOLDABLE COMMODE', 'Consumable', 80000.00
    UNION ALL SELECT 'GEN-CON-NONFOLDABLE-COMMODE', 'NON-FOLDABLE COMMODE', 'Consumable', 80000.00
    UNION ALL SELECT 'GEN-CON-ARM-STRING', 'ARM STRING', 'Consumable', 25000.00
    UNION ALL SELECT 'GEN-CON-SKIN-TRACTION', 'SKIN TRACTION', 'Consumable', 50000.00
    UNION ALL SELECT 'GEN-CON-WRIST-SPLIT-SUPPORT', 'WRIST SPLIT / SUPPORT', 'Consumable', 25000.00
    UNION ALL SELECT 'GEN-CON-WHEEL-CHAIR', 'WHEEL CHAIR', 'Consumable', 80000.00
    UNION ALL SELECT 'GEN-CON-WALKER', 'WALKER', 'Consumable', 40000.00
    UNION ALL SELECT 'GEN-CON-CLUTCHES', 'CLUTCHES', 'Consumable', 30000.00
    UNION ALL SELECT 'GEN-CON-FEMALE-BED-PAN', 'FEMALE BED PAN', 'Consumable', 20000.00
    UNION ALL SELECT 'GEN-SRV-ARTIFICIAL-FIXTURE', 'ARTIFICIAL FIXTURE CONSULT', 'Service', 10000.00
    UNION ALL SELECT 'GEN-SRV-NEUROSURGEON', 'NEUROSURGEON', 'Service', 20000.00
    UNION ALL SELECT 'GEN-SRV-EXTRA-GENERAL-WARD', 'EXTRA STAY FOR GENERAL WARD', 'Service', 150000.00
    UNION ALL SELECT 'GEN-SRV-EXTRA-PRIVATE-WARD', 'EXTRA STAY FOR PRIVATE WARD', 'Service', 250000.00
    UNION ALL SELECT 'GEN-CON-PHILADEPHIA-COLLAR', 'PHILADEPHIA COLLAR', 'Consumable', 50000.00
    UNION ALL SELECT 'GEN-CON-LUMBAR-COSETTE', 'LUMBAR COSETTE', 'Consumable', 40000.00
    UNION ALL SELECT 'GEN-CON-TOILET-SEATER', 'TOILET SEATER', 'Consumable', 60000.00
    UNION ALL SELECT 'GEN-CON-THORACOLUMBO-JACKET', 'THORACOLUMBOSACURAL JACKET', 'Consumable', 80000.00
    UNION ALL SELECT 'GEN-CON-FOODDROP-DEVICE', 'FOODDROP DEVICE', 'Consumable', 30000.00
    UNION ALL SELECT 'GEN-CON-CAST-POP', 'CAST (1 ROLL) / POP', 'Consumable', 25000.00
    UNION ALL SELECT 'GEN-CON-ELBOW-SUPPORT', 'ELBOW SUPPORT', 'Consumable', 40000.00
    UNION ALL SELECT 'GEN-CON-TRIPOD', 'TRIPOD', 'Consumable', 25000.00
    UNION ALL SELECT 'GEN-CON-STICK', 'STICK', 'Consumable', 15000.00
    UNION ALL SELECT 'GEN-SRV-BIO-DRESS', 'BIO DRESS', 'Service', 18000.00
    UNION ALL SELECT 'GEN-SRV-CENTRAL-LINE', 'CENTRAL LINE', 'Service', 150000.00
    UNION ALL SELECT 'GEN-CON-FINGER-SPUNT', 'FINGER SPUNT', 'Consumable', 50000.00
) seed
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
       OR UPPER(TRIM(existing.item_name)) = seed.item_name
);
