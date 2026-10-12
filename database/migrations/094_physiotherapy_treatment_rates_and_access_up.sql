-- Replace the active Physiotherapy price catalogue with the approved treatment
-- rates, and add long-running paid access entitlement tracking.

CREATE TABLE IF NOT EXISTS physiotherapy_patient_access (
    id BIGINT NOT NULL AUTO_INCREMENT,
    patient_id INT NOT NULL,
    valid_until DATE NOT NULL,
    source_invoice_id INT NULL,
    source_payment_id INT NULL,
    granted_by INT NULL,
    granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_physio_patient_access_patient (patient_id),
    KEY idx_physio_patient_access_valid_until (valid_until),
    KEY idx_physio_patient_access_invoice (source_invoice_id),
    KEY idx_physio_patient_access_payment (source_payment_id),
    CONSTRAINT fk_physio_patient_access_patient
        FOREIGN KEY (patient_id) REFERENCES patients(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_physio_patient_access_invoice
        FOREIGN KEY (source_invoice_id) REFERENCES invoices(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_physio_patient_access_payment
        FOREIGN KEY (source_payment_id) REFERENCES payments(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_physio_patient_access_granted_by
        FOREIGN KEY (granted_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE billable_items bi
INNER JOIN departments d
    ON d.id = bi.department_id
SET bi.is_active = 0,
    bi.updated_at = NOW()
WHERE d.department_name = 'Physiotherapy';

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
       'Service',
       d.id,
       'Physiotherapy treatment rate. Paid treatment grants Physiotherapy profile access for 30 years.',
       seed.unit_price,
       'Treatment',
       1,
       u.id,
       NOW(),
       NOW()
FROM (
    SELECT 'PHY-SRV-TREATMENT-ADULT' AS item_code, 'Physio Treatment - Adult' AS item_name, 100000.00 AS unit_price
    UNION ALL SELECT 'PHY-SRV-TREATMENT-CHILD', 'Physio Treatment - Children (0-18 yrs)', 80000.00
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
);

UPDATE billable_items bi
INNER JOIN departments d
    ON d.id = bi.department_id
SET bi.item_name = CASE bi.item_code
        WHEN 'PHY-SRV-TREATMENT-ADULT' THEN 'Physio Treatment - Adult'
        WHEN 'PHY-SRV-TREATMENT-CHILD' THEN 'Physio Treatment - Children (0-18 yrs)'
        ELSE bi.item_name
    END,
    bi.item_type = 'Service',
    bi.description = 'Physiotherapy treatment rate. Paid treatment grants Physiotherapy profile access for 30 years.',
    bi.unit_price = CASE bi.item_code
        WHEN 'PHY-SRV-TREATMENT-ADULT' THEN 100000.00
        WHEN 'PHY-SRV-TREATMENT-CHILD' THEN 80000.00
        ELSE bi.unit_price
    END,
    bi.unit = 'Treatment',
    bi.is_active = 1,
    bi.updated_at = NOW()
WHERE d.department_name = 'Physiotherapy'
  AND bi.item_code IN (
      'PHY-SRV-TREATMENT-ADULT',
      'PHY-SRV-TREATMENT-CHILD'
  );
