DELETE rp FROM role_permissions rp
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE p.permission_key IN (
    'view_billing_discounts',
    'apply_billing_discount',
    'cancel_billing_discount'
);

DELETE FROM user_permissions
WHERE permission_id IN (
    SELECT id FROM permissions
    WHERE permission_key IN (
        'view_billing_discounts',
        'apply_billing_discount',
        'cancel_billing_discount'
    )
);

DELETE FROM permissions
WHERE permission_key IN (
    'view_billing_discounts',
    'apply_billing_discount',
    'cancel_billing_discount'
);

DROP TABLE IF EXISTS billing_discounts;
