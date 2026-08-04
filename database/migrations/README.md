# Database Evolution Policy

`database/hospital.sql` is the baseline schema for a fresh installation.

After a release has been installed, all schema changes must be delivered as
numbered, paired migrations:

- `<number>_<name>_up.sql` applies the change.
- `<number>_<name>_down.sql` reverses the change when it is safe to do so.

The current Phase 0 migration order is:

1. `002_phase0_live_schema_alignment_up.sql`
2. `003_phase0_queue_workflow_up.sql`
3. `004_phase0_store_status_up.sql`

The missing `001` number is historical and is intentionally not reused.
Migration files are not replayed against an already aligned database. A
deployment process must record applied migration filenames and validate the
target schema before executing each file. The current files are one-time
alignment migrations and are not safe to execute twice without those checks.

The baseline includes the final Phase 0 schema so fresh installations do not
need to replay the alignment migrations. Existing installations use the
migrations to reach that same schema without deleting data.

Down migrations must be reviewed against live data before execution. In
particular, rolling back the Store status or dropping encounter events can
require data retention or archival work and must not be treated as an
automatic production rollback.
