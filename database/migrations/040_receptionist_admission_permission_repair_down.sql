-- No-op by design.
-- Receptionist admission access is part of the intended Migration 037 policy,
-- so rolling back this repair must not remove the baseline permission grants.

SELECT 1;

