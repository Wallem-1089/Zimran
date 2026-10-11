-- Seed Laboratory price-list services and replace active ECG catalogue entries.

UPDATE billable_items bi
INNER JOIN departments d
    ON d.id = bi.department_id
SET bi.is_active = 0,
    bi.updated_at = NOW()
WHERE d.department_name = 'ECG'
  AND bi.item_code NOT IN ('ECG-ECG', 'ECG-REVIEW');

UPDATE billable_items bi
INNER JOIN departments d
    ON d.id = bi.department_id
SET bi.item_code = 'LAB-BLOOD-GROUP',
    bi.item_name = 'BLOOD GROUP',
    bi.item_type = 'Service',
    bi.description = 'Laboratory service price list item.',
    bi.unit_price = 2000.00,
    bi.unit = 'Test',
    bi.is_active = 1,
    bi.updated_at = NOW()
WHERE d.department_name = 'Laboratory'
  AND bi.item_name = 'Blood Group'
  AND NOT EXISTS (
      SELECT 1 FROM billable_items existing WHERE existing.item_code = 'LAB-BLOOD-GROUP'
  );

UPDATE billable_items bi
INNER JOIN departments d
    ON d.id = bi.department_id
SET bi.item_code = 'LAB-GENOTYPE',
    bi.item_name = 'GENOTYPE',
    bi.item_type = 'Service',
    bi.description = 'Laboratory service price list item.',
    bi.unit_price = 15000.00,
    bi.unit = 'Test',
    bi.is_active = 1,
    bi.updated_at = NOW()
WHERE d.department_name = 'Laboratory'
  AND bi.item_name = 'Blood Genotype'
  AND NOT EXISTS (
      SELECT 1 FROM billable_items existing WHERE existing.item_code = 'LAB-GENOTYPE'
  );

INSERT INTO billable_items (
    item_code,
    item_name,
    item_type,
    department_id,
    description,
    unit_price,
    unit,
    is_active,
    created_by
)
SELECT seed.item_code,
       seed.item_name,
       'Service',
       d.id,
       seed.description,
       seed.unit_price,
       'Test',
       1,
       u.id
FROM (
    SELECT 'LAB-FULL-TEST' AS item_code, 'FULL TEST' AS item_name, 120000.00 AS unit_price, 'Laboratory service price list item.' AS description, 'Laboratory' AS department_name
    UNION ALL SELECT 'LAB-CRP', 'CRP', 30000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-FULL-ROUTINE', 'FULL ROUTINE', 70000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-D-DINNER', 'D-DINNER', 30000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-HALF-ROUTINE', 'HALF ROUTINE', 35000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-HBAIC', 'HBAIC', 30000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-O-POSITIVE-BLOOD', 'O+ POSITIVE BLOOD', 37000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-URINE-BENZ-JONES', 'URINE BENZ-JONES', 30000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-O-NEGATIVE-BLOOD', 'O- NEGATIVE BLOOD', 40000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-FSLP', 'FSLP', 30000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-PCV', 'PCV', 2000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-LIPID-PROFILE', 'LIPID PROFILE', 30000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-BLOOD-GROUP', 'BLOOD GROUP', 2000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-LFT-ALP', 'LFT/ALP', 15000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-MP', 'MP', 2000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-CLOTING-PROFILE', 'CLOTING PROFILE', 25000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-WIDAL', 'WIDAL', 4000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-SERUM-URIC-ACID', 'SERUM URIC ACID', 4000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-FBC', 'FBC', 8000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-HCV', 'HEPATITIS C (HCV)', 3000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-ESR', 'ESR', 3500.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-HVS', 'HIGH VALGINAL SWAB (HVS)', 6000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-RVS', 'RVS', 3000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-TFT', 'THYROID FUNCTION TEST (TFT)', 25000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-FBS-RBS-HB', 'FBS/RBS/HB', 2000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-CROSS-MATCHING', 'CROSS MATCHING', 2000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-SERUM-PSA', 'SERUM PSA', 30000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-OGTT', 'OGTT (ORAL GLUCOSE TOLERANCE TEST)', 4000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-E-U-CRE', 'E/U/Cre', 14000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-BLOOD-ASPIRATE-MCS', 'BLOOD ASPIRATE M/C/S', 12000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-URINE-ANALYSIS', 'URINE ANALYSIS', 2000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-SERUM-ELECTROPHORESIS', 'SERUM ELECTROPHORESIS', 25000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-HBASG', 'HBASg (HEPATITIS B)', 3000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-SERUM-PROTEIN', 'SERUM PROTEIN', 3000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-WOUND-MCS', 'WOUND M/C/S', 8000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-SERUM-PROTEIN-DIFF', 'SERUM PROTEIN + DIFF', 8000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-PREGNANCY-TEST', 'PREGNANCY TEST (PT)', 2000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-SERUM-VID', 'SERUM VI+D', 25000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-SERUM-CALCIUM', 'SERUM CA+ (CALCIUM)', 8000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-PT-PTTK', 'PT/PTTK', 14000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-HORMONAL-ASSAY', 'HORMONAL ASSAY', 25000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-AABX2', 'AABx2', 5000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-SERUM-PHOSPHATE', 'SERUM PO4- (PHOSPHATE)', 8000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-H-PYLORI', 'H-PYLORI', 4000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-INFERTILITY-PROFILE', 'INFERTILITY PROFILE', 25000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-STOOL-OCULT-BLOOD', 'STOOL FOR OCULT BLOOD', 4000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-AERUM-M-BAND-PROTEIN', 'AERUM-M-BAND PROTEIN', 25000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-SCREENING', 'SCREENING', 12000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-VDRL-SYPHILIS', 'V.DRL (SYPHILIS)', 3000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-URINE-MCS', 'URINE M/C/S', 6000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-BLOOD-DONATION', 'BLOOD DONATION', 10000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-MEDICAL-REPORT', 'MEDICAL REPORT', 10000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-ASPIRATE-MCS', 'ASPIRATE M/C/S', 10000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'ECG-ECG', 'ECG', 15000.00, 'ECG service price list item.', 'ECG'
    UNION ALL SELECT 'LAB-HAEMOGLOBIN', 'HAEMOGLOBIN', 2000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-ECHO', 'ECHO', 40000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-SFA', 'SFA', 3000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'ECG-REVIEW', 'ECG REVIEW', 40000.00, 'ECG service price list item.', 'ECG'
    UNION ALL SELECT 'LAB-MANTOUX', 'MANTOUX', 3000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-GENOTYPE', 'GENOTYPE', 15000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-AAFBX3', 'AAFBX3', 8000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-CD4-COUNT', 'CD4 COUNT', 30000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-URETRA-SWAB', 'URETRA SWAB', 8000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-BLOOD-CULTUER', 'BLOOD CULTUER', 25000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-KALINE-PHOSPHATE', 'KALINE PHOSPHATE', 6000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-BILIRIBIN', 'BILIRIBIN', 2000.00, 'Laboratory service price list item.', 'Laboratory'
    UNION ALL SELECT 'LAB-AAFSXI', 'AAFSXI', 2500.00, 'Laboratory service price list item.', 'Laboratory'
) seed
INNER JOIN departments d
    ON d.department_name = seed.department_name
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
);
