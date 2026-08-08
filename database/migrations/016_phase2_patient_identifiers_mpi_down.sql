/*
|--------------------------------------------------------------------------
| Non-destructive rollback - Phase 2 Milestone 2.2
|--------------------------------------------------------------------------
| Identifier, history, and duplicate-review tables predate this compatibility
| migration through preserved Migration 014. Medical history must not be
| dropped automatically. Rollback removes only settings introduced by 016.
*/

DELETE FROM system_settings
WHERE setting_key IN (
    'mpi.identifier_definitions',
    'mpi.duplicate_threshold',
    'mpi.fuzzy_search_threshold',
    'mpi.exact_match_priority'
);

SELECT 'Migration 016 rollback retained identifier and duplicate history.'
    AS message;
