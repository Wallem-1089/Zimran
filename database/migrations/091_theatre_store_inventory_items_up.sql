-- Seed Store inventory master items commonly requested/used by Theatre.
-- Store inventory currently supports only Drug and Consumable categories.
-- Physical theatre supplies/equipment are categorized as Consumable; medicines,
-- injections, anaesthetic agents, IV fluids/solutions, and medicated ointments
-- are categorized as Drug.

INSERT INTO inventory_items (
    item_code,
    item_name,
    category,
    unit,
    description,
    billable_item_id,
    is_active,
    created_by,
    created_at,
    updated_at
)
SELECT seed.item_code,
       seed.item_name,
       seed.category,
       'Unit',
       'Store inventory item for Theatre use.',
       NULL,
       1,
       u.id,
       NOW(),
       NOW()
FROM (
    SELECT 'THE-CON-001' AS item_code, 'DISPOSABLE SKIN STAPLER' AS item_name, 'Consumable' AS category
    UNION ALL SELECT 'THE-DRG-002', 'DREZ OINTMENT (IODINATED-POVIDONE & METRONIDAZOLE OINTMENT)', 'Drug'
    UNION ALL SELECT 'THE-DRG-003', 'DREZ SOLUTION (IODINATED-POVIDONE & METRONIDAZOLE OINTMENT)', 'Drug'
    UNION ALL SELECT 'THE-CON-004', 'PRE STERILIZED SURGICAL GENERAL PACK', 'Consumable'
    UNION ALL SELECT 'THE-CON-005', 'DISPOSABLE STERILE HIP PACK', 'Consumable'
    UNION ALL SELECT 'THE-CON-006', 'MINI COMPRESSOR NEBULIZER', 'Consumable'
    UNION ALL SELECT 'THE-DRG-007', 'POVIDERM ANTISEPTIC SOLUTION', 'Drug'
    UNION ALL SELECT 'THE-CON-008', 'EPIDURAL MINIPACK', 'Consumable'
    UNION ALL SELECT 'THE-CON-009', '3-BALL INCENTIVE SPIROMETER', 'Consumable'
    UNION ALL SELECT 'THE-DRG-010', 'DURACINE PLAIN BUPIVACAINE HCL', 'Drug'
    UNION ALL SELECT 'THE-CON-011', 'SUTURES', 'Consumable'
    UNION ALL SELECT 'THE-CON-012', 'SPINAL NEEDLES', 'Consumable'
    UNION ALL SELECT 'THE-CON-013', 'EUROFIX', 'Consumable'
    UNION ALL SELECT 'THE-CON-014', '3M LOBAN', 'Consumable'
    UNION ALL SELECT 'THE-CON-015', 'DISPOSABLE ELECTROSURGICAL', 'Consumable'
    UNION ALL SELECT 'THE-CON-016', 'DIATHERMY EXTRA', 'Consumable'
    UNION ALL SELECT 'THE-DRG-017', 'HEPARIN INJECTION', 'Drug'
    UNION ALL SELECT 'THE-CON-018', 'BIODRESS', 'Consumable'
    UNION ALL SELECT 'THE-CON-019', 'STERITIN ANTIBIOTICS (SOFRATULLE)', 'Consumable'
    UNION ALL SELECT 'THE-CON-020', 'PRIVAC', 'Consumable'
    UNION ALL SELECT 'THE-CON-021', 'OCTACARE', 'Consumable'
    UNION ALL SELECT 'THE-DRG-022', 'MORFINA SERRA 10MG', 'Drug'
    UNION ALL SELECT 'THE-CON-023', 'OPSITE', 'Consumable'
    UNION ALL SELECT 'THE-DRG-024', 'HAEMACCEL', 'Drug'
    UNION ALL SELECT 'THE-CON-025', 'MODULAR RESECTION PROSTHESIS', 'Consumable'
    UNION ALL SELECT 'THE-DRG-026', 'NEOSTIGMINE METHYLSULFATE PROSTHESIS', 'Drug'
    UNION ALL SELECT 'THE-DRG-027', 'HEAVY MARCAINE INJ', 'Drug'
    UNION ALL SELECT 'THE-CON-028', 'STERILE PULSE FLUSHING SYSTEM', 'Consumable'
    UNION ALL SELECT 'THE-CON-029', 'PULSE LEVAGE', 'Consumable'
    UNION ALL SELECT 'THE-CON-030', 'BIPORAL HIP PROSTHESIS', 'Consumable'
    UNION ALL SELECT 'THE-DRG-031', 'INJECTION KETAMINE', 'Drug'
    UNION ALL SELECT 'THE-CON-032', 'SOLISET', 'Consumable'
    UNION ALL SELECT 'THE-DRG-033', 'ISOFLURANE I.P', 'Drug'
    UNION ALL SELECT 'THE-DRG-034', 'INJ ATROPINE', 'Drug'
    UNION ALL SELECT 'THE-DRG-035', 'INJ SODIUM BICARBONATE', 'Drug'
    UNION ALL SELECT 'THE-CON-036', 'BD SYRINGE', 'Consumable'
    UNION ALL SELECT 'THE-CON-037', 'INFUSION PUMP', 'Consumable'
    UNION ALL SELECT 'THE-CON-038', 'NEBULIZER', 'Consumable'
    UNION ALL SELECT 'THE-CON-039', 'BANDAGES', 'Consumable'
    UNION ALL SELECT 'THE-CON-040', 'BLADES', 'Consumable'
    UNION ALL SELECT 'THE-DRG-041', 'CHLORIDE', 'Drug'
    UNION ALL SELECT 'THE-CON-042', 'DISPOSABLE SURGICAL GOWN', 'Consumable'
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
    FROM inventory_items existing
    WHERE existing.item_code = seed.item_code
       OR UPPER(TRIM(existing.item_name)) = seed.item_name
);
