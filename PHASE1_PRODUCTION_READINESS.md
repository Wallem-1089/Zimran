# Phase 1 Production Readiness Report

## Recovery safety addendum (2026-08-05)

Database-writing tests now require an explicit `testing` environment and
dedicated `hms_test_*` database. The live bootstrap refuses testing mode.
Baselines are database-neutral, migrations are checksummed, destructive test
recreation requires confirmation and a backup gate, and operations are logged.

The database incident was unrecoverable because binary logging was off and no
backup existed. Phase 1, Phase 1.8, and Phase 2.1 tests now pass on the
dedicated reconstructed test database. This does not mark Milestone 2.2
complete.

## Phase 1.8 Critical Pre-Clinical Remediation Addendum

On 5 August 2026, targeted Phase 1.8 remediation completed the final
pre-clinical gates without introducing Phase 2 functionality:

- `patients.gender` now consistently supports `Male`, `Female`, `Other`, and
  `Unknown` in the baseline, migration 012, service validation, forms, search,
  review flow, and tests;
- migration 012 recorded eight historical empty ENUM sentinels in
  `phase1_patient_gender_repair` and repaired them explicitly to `Unknown`;
- patient registration and update controllers no longer duplicate the audit
  records written atomically by `PatientService`;
- missing or invalid environment configuration now defaults to production;
- development authentication fallback requires both `HMS_APP_ENV=development`
  and `HMS_ENABLE_DEV_AUTH_BYPASS=true` in protected server/process
  configuration;
- unauthenticated nested routes now redirect to the absolute application login
  URL;
- the focused Phase 1.8 test and full Phase 1 regression passed against the
  live database, and repository-wide PHP syntax validation passed.
- live HTTP checks passed for fail-closed redirects, real administrator
  login/logout, protected core routes, patient form values, valid review, and
  invalid-gender rejection;
- isolated migration checks passed for baseline plus migrations 005–012,
  migration 012 down/up, and the unsafe-contraction refusal path.

## Report Scope

This report records the Phase 1 Milestone 1.7 hardening pass performed on 4 August 2026 against the current repository, Apache, PHP, and the live `hospital_management_system` MariaDB database.

The pass covered Authentication, password management, Administration, settings, patients, encounters, workspace authorization, transfers, receipt, queues, doctor assignment, lifecycle protection, timelines, audit history, schema evolution, performance, and documentation. No Phase 2 clinical functionality was implemented.

## Verification Environment

| Component | Verification state |
|---|---|
| Apache | Live HTTP route checks passed on `http://localhost/hospital_management_system` |
| Database | XAMPP MariaDB 10.4.32, native PDO prepared statements |
| PHP | Repository-wide syntax validation passed for 176 PHP files |
| Live service test | `test/phase1_regression_test.php` passed against MySQL |
| Existing tests | Database, helpers, layout, session, audit, and settings tests passed |
| Migration test | Fresh baseline plus migrations 005–012 passed; migration 012 down/up passed in isolation, with the historical 011–005 reverse result retained |

The regression test creates clearly named `PhaseOne RegressionPatient` records and completed/cancelled encounters so workflow history remains traceable. Production CI should run the test against a dedicated disposable database rather than operational data.

## Changes Made During Milestone 1.7

- Integrated `security.lockout_threshold` with `AuthService`, retaining the configuration fallback.
- Integrated `security.session_timeout_minutes` with persistent session expiry, retaining the configuration fallback.
- Hardened session cookies with cookie-only and strict mode, HttpOnly, SameSite=Lax, and conditional Secure behavior over HTTPS.
- Added CLI-only repository session storage so automated tests do not depend on the Apache session directory.
- Synchronized user create/edit operations with the primary `user_departments` membership in the same transaction.
- Added password-history persistence to administrator password resets.
- Made patient registration and patient update transactional and auditable; patient update now locks the target row.
- Rejected encounter creation and transfer destinations that do not map to a supported encounter lifecycle state.
- Prevented generic encounter editing from changing department ownership or doctor assignment.
- Converted the legacy `VisitService::transferDepartment()` method into a compatibility wrapper over `transferVisit()`.
- Removed the final direct module SQL query from `modules/visits/edit.php`.
- Added composite audit query indexes through migration 010.
- Repaired five historical invalid encounter ENUM sentinel values through reversible migration 011.
- Made audit failures propagate to the owner of an existing transaction so business state cannot commit without its required audit record.
- Added a repeatable Phase 1 service regression program.

## Services Reviewed

| Service | Review result |
|---|---|
| `AuditService` | Transaction-aware inserts and indexed filtering verified. Legacy convenience methods return `void`; `log()` remains the authoritative success-returning API. |
| `AuthService` | Account state, password verification, settings-backed lockout threshold, and backward-compatible responses verified. |
| `DashboardService` | Aggregated SQL, structured response, administrator view audit, and empty-state behavior verified. |
| `DepartmentService` | CRUD lifecycle, duplicate prevention, transaction boundaries, history preservation, and summary queries verified. |
| `EncounterEventService` | Sole encounter-event insert boundary verified. |
| `EncounterStateService` | Known states, terminal protection, transfer/receive/assignment prerequisites verified. |
| `PatientService` | Registration and update transaction/audit defects corrected and verified. Read methods remain backward-compatible raw reads. |
| `PermissionService` | Administrator override, database-first permissions, department scope, and compatibility fallback verified. |
| `QueueService` | Duplicate prevention, row locking, receipt prerequisite, transitions, audit/event integration, and ordering verified. |
| `RoleService` | Lifecycle, uniqueness, transactions, and audit integration verified. |
| `SessionService` | Persistent creation, expiry, settings timeout, termination, history, and active department verified. |
| `SettingsService` | CRUD, validation, typed retrieval, request cache, bulk updates, reset, export, history, and redaction verified. |
| `UserDepartmentService` | Assignment, primary normalization, switching, removal, inactive-department protection, and audit verified. |
| `UserService` | User lifecycle, locking, password history, primary department synchronization, transactions, and compatibility methods verified. |
| `VisitService` | All required public APIs remain present; workflow delegation, lifecycle validation, transaction/event/audit behavior, and compatibility wrappers verified. |

Empty `BillingService`, `ConsultationService`, `LaboratoryService`, and `PharmacyService` files remain planned placeholders and were not treated as implemented services.

## Service Contract Findings

Newer mutation methods consistently return structured arrays containing `success` and `errors`, plus compatibility identifiers where applicable. `DashboardService` and `SettingsService` also provide `data` where useful.

The following legacy exceptions are retained to avoid breaking callers:

- `VisitService::transferDepartment()` returns `bool`;
- `AuditService::log()` and `DashboardService::recordDashboardView()` return `bool`;
- session state methods and several legacy user authentication helpers return `void`;
- read methods commonly return entity arrays, lists, scalar counts, or `null` rather than mutation envelopes.

These exceptions are documented compatibility contracts. They should be replaced only through additive methods and an approved deprecation cycle.

## Regression Testing Report

### Live service workflow

The automated regression verified:

1. administrator override and Administration catalogue reads;
2. duplicate role and department prevention;
3. user creation, editing, primary department synchronization, password reset history, lock, unlock, and deactivation;
4. secondary department assignment, active switching, return to primary, and removal;
5. password hash/verification contract;
6. patient registration and transactional update audit;
7. unsupported initial department rejection;
8. encounter creation and automatic queue entry;
9. generic encounter edit success and transfer-bypass rejection;
10. duplicate active queue prevention and queue ordering;
11. queue call, start, and completion;
12. unsupported transfer destination rejection;
13. transfer to Nursing, pending-receipt service protection, receipt, service start, and completion;
14. transfer to Doctor, receipt, active doctor assignment, and completion;
15. completed encounter protection against transfer, assignment, queueing, and reopening;
16. expected encounter events and timeline rendering;
17. encounter audit trail completeness;
18. administrator dashboard data contract;
19. persistent session creation and administrator termination.
20. forced audit-insert failure propagation and rollback of the surrounding business write.

### HTTP route matrix

| Route group | Result |
|---|---|
| Authentication login | `200`; valid CSRF admin login redirected `302` to dashboard |
| Authenticated administrator dashboard | `200` |
| Logout | `302` redirect as expected |
| Dashboard, Patients, Encounter creation, Workspace | `200` for valid records |
| Completed encounter transfer/assignment pages | `403` as expected from lifecycle/authorization protection |
| User, Role, Permission, Department Administration | `200` for administrator |
| Security dashboard, sessions, histories, lockouts, audit viewer | `200` for administrator |
| Settings dashboard, category, create, edit, history, export | `200` for administrator |
| Missing patient, user, or encounter | `404` |
| POST without CSRF | `403` for patient/encounter/Administration mutation routes tested |
| Doctor access to Administration, Security, Settings | `403` |
| Doctor access to Doctor-department encounter workspace | `200` |
| Doctor access to another department workspace | `403` |

All detected POST routes call the shared CSRF validation boundary.

## Database Review

### Baseline and migrations

- `database/hospital.sql` remains the destructive fresh-install baseline. It must never be run against an existing installation because it drops and recreates the named database.
- Existing installations advance through paired migrations.
- Fresh Phase 1.8 verification imported the updated baseline and applied migrations 005–012 successfully in an isolated database.
- Reverse execution from 011 through 005 also passed in the isolated verification database.
- Migration 012 was applied to the live database during Phase 1.8. Its down/up
  path passed in an isolated database with no dependent values; it was not run
  down on live data because live `Unknown` records make contraction unsafe by
  design.
- Phase 0 alignment migrations 002–004 are for existing pre-alignment installations and are intentionally not replayed after the current baseline.

### Live integrity results

After repair, all checks returned zero:

- users missing their active primary membership;
- users with multiple active primary memberships;
- orphaned visit-patient or visit-department references;
- duplicate active queue entries;
- pending receipt state without a matching transfer;
- encounter events without encounters;
- audit actors without users.

Five historical Store encounters held MySQL's invalid empty ENUM sentinel. Migration 011 retained their previous value in `phase1_visit_status_repair` and repaired the live rows to `Store`.

Eight historical patients held MySQL's invalid empty gender ENUM sentinel.
Migration 012 retained their patient IDs and original sentinel marker in
`phase1_patient_gender_repair`, expanded the domain, and repaired those values
to `Unknown`. Post-migration verification found zero empty patient gender
sentinels.

### Migration limitations

There is no automated applied-migration ledger, checksum validation, or migration runner. Operators must track filenames and verify the target schema manually. This is a deployment-process priority before the first external production release, but it does not block Phase 2 development.

## Performance Review

### Improvements applied

Migration 010 added:

- `(module, created_at)`;
- `(event_type, created_at)`;
- `(severity, created_at)`;
- `(visit_id, created_at)`

to `audit_logs`. Query-plan verification confirmed event/date filtering uses `idx_audit_event_created`.

### Query-plan findings

| Area | Finding | Priority |
|---|---|---|
| Audit viewer/security summaries | Composite indexes support current filters and chronological reads. | Complete |
| Queue ordering | Department/status/position index narrows candidates; `position IS NULL` still causes a small filesort. | Low |
| Encounter department reads | Department and department/receipt indexes exist; the optimizer selected the department index on the small current dataset. | Monitor |
| Administration dashboard | Uses aggregate SQL and indexed joins; no N+1 controller loops were found. | Complete for current scale |
| Patient search | Leading-wildcard multi-column searches require a table scan and filesort. | Medium before large patient volumes |
| Dashboard caching | No shared cache exists; request-local settings caching is implemented. | Low at current scale |

Recommended future patient-search work is a normalized search strategy or database full-text/search index, introduced only after representative volume testing.

## Security Review

### Verified controls

- PDO native prepared statements;
- centralized authentication and authorization;
- database-driven role permissions with compatibility fallback;
- explicit administrator override;
- active-department and encounter-department isolation;
- server-side HTTP 403 enforcement;
- shared CSRF tokens and constant-time comparison;
- strict, cookie-only, HttpOnly, SameSite session cookies and HTTPS Secure flag;
- session ID regeneration on login;
- persistent session expiry and administrative termination;
- password hashing with `PASSWORD_DEFAULT`;
- failed-login counting and settings-backed lockout threshold;
- password reset/change history;
- immutable application audit viewer;
- sensitive setting audit redaction;
- generic database and patient write failure messages.

### Deployment security gates

Environment resolution now fails closed: missing or invalid `HMS_APP_ENV` values resolve to production and authentication bypass is disabled. Developers may opt in only through protected process/server configuration using `HMS_APP_ENV=development` together with `HMS_ENABLE_DEV_AUTH_BYPASS=true`. Production deployment must continue to disable PHP error display, enable HTTPS, protect database credentials outside the web root, and verify writable runtime paths.

### Remaining security debt

- historical password reuse prevention, complexity enforcement, and password expiry are not implemented;
- two-factor authentication is settings metadata only;
- settings marked sensitive are redacted in history but not encrypted at rest;
- database-level prevention of privileged audit mutation is not implemented;
- trusted-proxy handling for forwarded client IP addresses requires deployment-specific configuration;
- a Content Security Policy and broader response security headers are not centrally configured.

## Settings Integration Review

Safely migrated during Milestone 1.7:

- failed-login lockout threshold;
- persistent session timeout.

Compatibility values still requiring isolated adoption work:

- date and time rendering formats;
- currency rendering;
- hospital branding/contact values in layouts;
- encounter and patient number formats;
- default encounter department;
- queue automatic-enqueue and reset behavior;
- reporting limits and default date ranges;
- maintenance/debug behavior;
- password minimum/complexity/expiry enforcement.

These should not be migrated as a bulk rewrite. Each consumer should receive a fallback, tests, and a rollback path.

## Code Quality Review

- No direct SQL remains under `modules/`.
- The required `VisitService` public APIs remain unchanged.
- Generic encounter editing no longer duplicates transfer or doctor-assignment behavior.
- Event insertion remains centralized in `EncounterEventService`.
- Administration and workflow mutations use service-owned transactions.
- Empty clinical service placeholders remain obvious planned debt.
- `VisitService` is large and contains legacy formatting/compatibility methods; decomposition should occur only behind its public facade during future module work.
- Encounter number generation uses a count-derived sequence and should receive a concurrency-safe sequence strategy before sustained multi-node/high-volume registration.
- Role inheritance and database-enforced one-primary membership remain future enhancements.

## Production Readiness Checklist

| Check | Status | Evidence/condition |
|---|---|---|
| PHP syntax | Pass | Repository-wide segmented lint passed after Phase 1.8 changes |
| Database connectivity | Pass | Existing and regression tests connected through PDO |
| Baseline installation | Pass | Isolated baseline import succeeded |
| Migration forward/reverse order | Pass with safe-down guard | Fresh 005–012 and isolated 012 down/up passed; 012 applied live and refuses unsafe contraction while Other/Unknown records exist |
| Live referential integrity | Pass | Integrity queries returned zero violations |
| Service compatibility | Pass | Required VisitService APIs and routes retained |
| Transaction integrity | Pass with documented legacy return exceptions | Workflow/admin mutations verified; nested audit failure rollback tested; compatibility void/bool methods retained |
| Authentication | Pass | Real administrator login and logout verified |
| Authorization | Pass | Administrator and Doctor HTTP matrices verified |
| CSRF | Pass | Missing token rejected; valid login token accepted |
| Session handling | Pass | Persistent creation, timeout source, termination, and cookie flags verified |
| User/role/permission/department administration | Pass | Live service and route checks |
| Settings | Pass | Existing settings test and routes passed |
| Dashboard | Pass | Live structured aggregates and route passed |
| Patients | Pass | Transactional create/update and audit passed |
| Encounters/workspace | Pass | Create, load, authorization, and read-only behavior passed |
| Queue | Pass | Ordering, duplicate protection, receipt prerequisite, transitions passed |
| Transfer/receive/assignment | Pass | Full live workflow passed |
| Timeline/events | Pass | Required event catalogue and rendered timeline passed |
| Audit logging | Pass | Workflow and Administration trails verified; indexes added |
| Production environment configuration | Pass for authentication default | Missing/invalid values resolve to production; development fallback requires two explicit protected environment values. HTTPS, credentials and PHP display settings remain deployment gates. |
| Automated migration ledger | Conditional | Manual tracking remains required before first external production rollout |
| Concurrency load test | Conditional | Row locks reviewed; multi-worker stress suite remains to be built |

## Phase 2 Readiness

The platform is ready to begin Phase 2 design and implementation for Medical Records, Clinical Documentation, Consultation, Nursing, Laboratory, Radiology, Pharmacy, Billing, and Reporting.

Before each clinical module starts, it must define:

- additive tables linked to `visit_id` where clinically appropriate;
- explicit module permissions and department scope;
- lifecycle prerequisites and queue ownership;
- transaction, encounter-event, and audit boundaries;
- immutable/sign-off/amendment rules for clinical data;
- module-specific indexes and regression cases;
- settings consumed through `SettingsService`.

The three critical documentation-checkpoint defects were resolved in Phase 1.8. No unresolved remediation defect blocks Phase 2 planning. The remaining conditional deployment items above must be closed before an external production release.

## Phase 2.4 foundation checkpoint

Migration 019 and its longitudinal clinical services were verified without
changing the Phase 1 Administration contracts. Phase 1 authentication,
authorization, users, departments, settings, audit, queue, transfer, receive,
assignment and dashboard regression tests continue to pass on the dedicated
test database. This note does not alter the original Phase 1 readiness verdict.

**Critical pre-clinical remediation complete — ready to begin Phase 2 planning.**

**Phase 1 Complete – Ready to Begin Phase 2.**
