# Medical Documents Deployment

Status: **Implemented in Phase 2 Milestone 2.5**. This guide covers deployment
of secure local storage. Object storage and malware-scanner integration remain
**Planned**.

## Storage root

`SecureLocalDocumentStorage` reads `HMS_DOCUMENT_STORAGE_ROOT`. When unset, the
default is `C:\xampp\hms_secure_documents`, outside the Apache document root.
The configured path must be absolute, dedicated to this application, writable
by the PHP/Apache account, and unreadable by unrelated OS users.

The provider creates:

```text
<root>/
  available/
  quarantine/
  .htaccess
  web.config
```

Files use random 256-bit names partitioned by a two-character prefix. Patient
names, hospital numbers, original filenames, and database IDs never appear in
physical names. The database stores only an opaque storage key.

If a project-local storage root is unavoidable, Apache must honor `.htaccess`,
directory listing and CGI/script handlers must be disabled, and an HTTP probe
must confirm denial before deployment. External storage remains the production
recommendation. Stored files are never linked directly; the authorized download
controller is the only delivery path.

## Permissions and web-server policy

- Grant the PHP process read/write/create access only to the dedicated root.
- Deny web-server URL mapping to the root.
- Keep directory permissions restrictive (`0700`) and files at `0600` where the
  host supports POSIX modes.
- Do not place secrets, executable scripts, or public assets in this root.
- Do not expose storage paths or keys in logs, HTML, JSON, or error pages.

## Upload and quarantine policy

The mandatory hardcoded ceiling is 40 MiB; the seeded operational limit is
10 MiB. The immutable allowlist is PDF, JPEG, PNG, and plain text. Settings can
enable a subset but cannot add executable or scriptable content.

No malware scanner is currently installed. Every version is therefore labelled
`Not Scanned`; no record is labelled clean. With
`documents.malware_scanning_required=true`, uploads remain `Quarantined` and
cannot be downloaded. A future scanner should inspect quarantine content,
record an external scan reference, and call the storage promotion boundary only
after a clean result. ClamAV or an equivalent is **Planned**, not simulated.

## Backup and retention

Database backups preserve metadata and storage references but not file bytes.
A production recovery point must pair the SQL dump with a consistent backup of
the secure storage root. Preserve immutable versions for at least the configured
retention horizon. Ordinary routes archive or mark records entered in error;
they do not physically delete files or metadata. Automated retention purge is
**Planned**.

Before migration or deployment:

1. Verify a current SQL backup and a storage backup.
2. Verify the storage root resolves outside the public document root.
3. Verify the PHP account can create, read, and remove a test file.
4. Verify direct HTTP access is impossible.
5. Run tests with `HMS_APP_ENV=testing`, a dedicated test database, and an
   isolated `HMS_DOCUMENT_STORAGE_ROOT`.

## Failure and recovery model

Filesystem operations cannot join a MySQL transaction. Upload/replacement first
stores an opaque file, then commits metadata, version, audit, and optional
encounter event atomically. Any database/audit/event failure triggers
compensating file removal. Storage failure occurs before database mutation.
Unexpected process termination can still leave an unreferenced file; a future
maintenance job may compare storage keys to version rows and quarantine orphans.

## Object-storage compatibility

`DocumentStorageInterface` isolates storage responsibilities. A future object
provider must preserve opaque keys, private buckets, server-side authorization,
checksum verification, quarantine semantics, streaming, and compensating
cleanup. Public object URLs and unsigned permanent links are not compatible
with this architecture.
