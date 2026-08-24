SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM role_permissions
WHERE permission_id IN (
    SELECT id FROM permissions
    WHERE permission_key IN (
        'create_billing_request',
        'view_billing_requests',
        'review_billing_request',
        'cancel_billing_request'
    )
);

DELETE FROM permissions
WHERE permission_key IN (
    'create_billing_request',
    'view_billing_requests',
    'review_billing_request',
    'cancel_billing_request'
);

DROP TABLE IF EXISTS billing_requests;

SET FOREIGN_KEY_CHECKS = 1;
