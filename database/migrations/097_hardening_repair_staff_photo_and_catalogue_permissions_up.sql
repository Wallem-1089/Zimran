-- Repair hardening drift found during the 2026-10-12 regression pass.
-- Some environments recorded migration 081 without the staff photo column,
-- and older test fixtures can retain Accounts catalogue mutation permissions.

ALTER TABLE staff_profiles
    ADD COLUMN IF NOT EXISTS profile_photo_path VARCHAR(255) NULL AFTER official_email;

DELETE rp
FROM role_permissions rp
INNER JOIN roles r ON r.id = rp.role_id
INNER JOIN permissions p ON p.id = rp.permission_id
WHERE r.role_name IN ('Accounts', 'Accountant')
  AND p.permission_key IN (
      'create_billable_items',
      'edit_billable_items',
      'manage_billable_item_status'
  );

