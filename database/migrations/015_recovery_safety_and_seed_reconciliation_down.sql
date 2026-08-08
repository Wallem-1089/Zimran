/*
| The ledger and reconciled grants/history are retained intentionally.
| Dropping them would remove deployment evidence and authorization state.
| This recovery migration therefore has no automatic destructive rollback.
*/
SELECT 'Migration 015 rollback is intentionally non-destructive.' AS message;
