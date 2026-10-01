-- No-op rollback.
-- Records Officer already had broad Records governance permissions before this
-- alignment; removing copied grants automatically could revoke legitimate
-- Medical Records access. Adjust role permissions manually from the matrix if
-- this policy is reversed.

SELECT 1;
