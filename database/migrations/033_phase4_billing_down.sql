DELETE FROM role_permissions
WHERE permission_key IN (
    'view_billing',
    'create_patient_charge',
    'cancel_patient_charge',
    'create_invoice',
    'record_payment',
    'view_receipts'
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_billing',
    'create_patient_charge',
    'cancel_patient_charge',
    'create_invoice',
    'record_payment',
    'view_receipts'
);

DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS patient_charges;
