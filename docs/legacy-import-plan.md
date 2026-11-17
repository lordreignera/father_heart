# Historical data migration — hold before import

The source `fathersheart` database remains read-only. The new application uses
`fathersheart2`. Do not run the included legacy PHP code: its connection file
contains credentials and may point at the new database. The legacy folder is
gitignored and must not be served by the Laravel web root.

## Source-to-target mapping

| Legacy | New tables | Rule |
| --- | --- | --- |
| `all_districts`, `district` | `districts`, `subcounties` | Match by reviewed name/code; retain unrecognized raw names. Never guess a district from a subcounty name alone. |
| `distribution` plus each of the 189 mission tables | `distribution_events` | One event per physical mission table; retain every registry row separately in `legacy_source_records`, including duplicate names and the 9 unlisted tables. |
| Each mission row and the 8 `recipients` rows | `people`, `assessments` | Preserve each original row and all 41/40 columns. No automatic person deduplication. Use `(source database, source table, source ID)` as the immutable identity. |
| `followup` | `assessments.legacy_followup` | Preserve the exact source value in the encrypted original; the boolean target cannot represent every possible source value. Map only understood values and do not equate them with a confirmed handover or a completed follow-up. |
| `givemobility`, measurements, questionnaire answers | `assessments` | Keep original value in encrypted `legacy_source_records.raw_payload`; map reportable values to typed columns only after checking codes and units. |
| `afterr`, `beforee`, `audio`, `images` | Future private media table | Keep original paths. Never display publicly without a separate valid consent record. |
| Device-specific stock tables and `inventory` | Future inventory snapshots/ledger | Preserve source snapshots; do not infer opening stock or issue transactions from these inconsistent counters. |
| `staff`, `volunteer`, `employee` | Future team history + fresh `users` | Preserve names/phones, never import SHA-1 password hashes as Laravel passwords. |
| `ratings` | Future feedback table | Retain all 59 source rows and review person/staff references. |

## Import gates

1. Take verified backups of both databases and a manifest of source-table row counts.
2. Use a SELECT-only source connection and a separate Laravel import command.
3. Dry-run every source table; report duplicates, missing locations, unknown device
   codes, invalid dates, unresolved photos and record-count differences.
4. Write in batches to `fathersheart2`, storing an encrypted raw payload, checksum,
   source key, mapped target ID and exception status for every row. Reruns must be
   idempotent and must never truncate or overwrite staff corrections.
5. Reconcile source/target counts per table, sample full records and exceptions with
   staff, then approve cutover. No public photo release or stock opening balance is
   inferred from historical data.

Encrypted originals and recipient records have been imported as documented below. The
cutover is not yet approved: identities, uncertain locations, stock and media require review.

## Current preparation

`php artisan legacy:audit` counts every source table using a read-only database session and writes an aggregate report to private storage. It identifies recipient-shaped tables by columns rather than assuming every event name corresponds to a physical table. This is an audit only: it neither backs up nor copies records, and its counts may change while the legacy system is in use.

### Aggregate audit on 16 November 2026

- All 215 source tables were counted without errors. There are 190 recipient-shaped tables containing 17,768 rows: 189 mission-shaped tables with 17,760 rows and the separate `recipients` table with 8 rows. These are rows, not necessarily unique people.
- Among recipient-shaped rows, 7,785 have a blank phone, none have a blank name, and no duplicate IDs were found within a table. These checks do not establish whether phones are valid, people are duplicates, or IDs correspond between tables.
- The distribution registry has 187 rows but 180 distinct names under the database collation; all registry names matched a table in the metadata lookup. Matching names alone does not establish that duplicate registry entries represent the same event.
- The other 25 tables also need explicit preservation and review, including locations, stock snapshots, images, ratings, staff and volunteers. Recipient counts alone cannot prove a complete transfer.
- The new assessment details screen currently displays only part of the historical questionnaire, and there is no operational mapping importer or staff-facing original-record review. Source dates, location matches, devices, image references, ambiguous answers and all remaining source fields still require a detailed dry run and review.
- The target had one person, zero assessments and zero provenance records at audit time. Counts are a point-in-time observation, not a backup or proof of a stable snapshot.
- All 17,768 recipient date strings have a slash-separated date shape, but shape alone is not validity or proof of semantics. The available historical `process.php` writes `m/d/Y`, and `process_edit.php` overwrites the same date on edit. Do not automatically label that value as the original assessment date; preserve it and review reporting semantics. Historical inventory code uses `d/m/Y`, so a single database-wide format is unsafe.

The aggregate report includes table names, column names, counts and data-quality totals, not patient rows. It is stored in `storage/app/private/legacy-audits` and should remain private. Its `complete` flag means table counting and these limited checks succeeded; it does not mean migration readiness or complete field validation.

The historical `fathersheart` database contains both MyISAM and InnoDB tables. Its storage engine must not be changed as part of the transfer. The new target was initially mostly MyISAM and has now been converted as documented below. This is only one import prerequisite: the importer, mapping, media handling, constraints and reconciliation are still outstanding.

## Backup and engine rehearsal, 16 November 2026

The user confirmed that neither system was in use. Local SQL dumps for both databases were created in `storage/app/private/legacy-backups/20261116-140852-fd1ca66b`. These contain sensitive information and are not encrypted; restrict machine/database access and arrange protected off-machine backups. SQL dump files are gitignored. No media-file backup is included.

Both files were restored into separate test databases. All 215 source and 29 target tables were present, and exact row counts matched the corresponding originals for every table. This checks restore execution and row counts, not full content equivalence, media, privileges or application behavior. The isolated restore databases are `fathersheart_restoretest_fd1ca66b` and `fathersheart2_restoretest_fd1ca66b`; they also contain sensitive data.

On the restored target copy, all 27 MyISAM tables converted to InnoDB successfully, and row counts for all 29 tables still matched the live target.

## Approved target conversion, 16 November 2026

After explicit user approval, a fresh target dump was saved in `storage/app/private/legacy-backups/20261116-innodb-preflight-690e` and restored to `fathersheart2_preflight_690e`. Before conversion, row counts and MySQL extended checksums matched across all 29 target tables, and `mysqlcheck --check` reported all tables OK. All 27 MyISAM tables in `fathersheart2` were then converted to InnoDB, without changing `fathersheart`. Afterward, every target table passed `mysqlcheck`, and row counts and MySQL extended checksums again matched the restored pre-conversion copy. MySQL checksums are useful checks, not cryptographic proof of every byte or application behavior.

Only one foreign-key constraint currently exists in the live target (for inventory movements); changing table engines does not create the missing constraints. Investigate data integrity and plan missing constraints separately. The server default is MyISAM, so Laravel's MySQL connection now explicitly requests InnoDB for future schema creations; this does not change the server-wide default or other databases.

The additional backup and restore database contain sensitive data, are local and unencrypted, and include no media files. Both systems must remain idle until a consistent migration and cutover are agreed.

## Original-record staging, 16 November 2026

The user requested the transfer. `legacy:stage` was first tested on the restored databases and run in dry-run mode against the original. The `--write` phase then staged 18,208 source rows from all 215 source tables in `fathersheart2.legacy_source_records`; four source tables were empty and are included in the batch's per-table manifest. Each row has a stable source identity, a SHA-256 checksum and an encrypted original payload. Every table was counted, the source rows were reread for changes, and each stored payload was decrypted and checked inside one InnoDB transaction before commit. The command refuses to overwrite an existing archive. Three automated tests cover dry-run behavior, encrypted staging, overwrite refusal and rollback on malformed data. The command uses a read-only source session but the configured account has not been independently restricted to SELECT-only.

At that stage the records had status `staged`. The source includes sensitive health and staff data; preserve the application encryption key securely, because losing or changing it makes the encrypted originals unreadable. Do not publish raw payloads or use the stored historical password hashes for login. The original system was not changed.

Do not run staging again against this target: the archive intentionally refuses an existing dataset.

## Recipient mapping and outstanding review

- A rehearsal and live import mapped 17,768 recipient-shaped source rows to 17,768 historical assessments and 17,768 new person entries, alongside the one existing person. Each source row is treated separately, not as proof of a unique person. The 189 physical mission tables became historical event placeholders; the separate `recipients` table is not assigned an invented event. Their original registry entries remain available as historical references.
- `legacy:reconcile-recipients` compared every mapped name, phone, selected questionnaire fields and event link against the encrypted originals: 17,768 checked and zero checksum, identity, mapped-field, event or missing-link errors. This is a mapping check, not proof that source data or inferred real-world identities are correct. The mapper is resumable and does not overwrite imported records on rerun.
- The UI shows the original historical questionnaire under the assessment, labels entries unverified and does not claim that old dates were assessment dates, devices were handed over, or media consent was granted. Historical entries with unknown registration dates are excluded from registration-date filters and new-registration statistics. Staff may need to review or merge repeated people through an appropriate future workflow; no automatic merge was performed.
- Exact unique name matching assigned 17,333 districts and 7,075 subcounties. A further 435 district values could not be matched unambiguously, and 9,020 nonblank subcounty values under matched districts were unmatched or ambiguous. These matches are suggestions requiring review; original names remain visible. No inferred values replaced existing staff choices.
- The other 440 originals remain preserved; authorized staff can review historical location, event-registry, team, feedback, image-reference and inventory records separately. Staff accounts were not created automatically from historical usernames or password hashes: super administrators can create accounts with new credentials and appropriate roles or explicitly link an existing non-super-admin account after confirming identity. Sixteen historical stock item types were added without opening balances or movements; an approved physical stock count is still required.
- `legacy:media-audit` found 11,434 before/after references to existing image files, 6,273 missing references, and 42 references to files that did not pass an image check; 17,787 reference slots were empty. Existing-image references correspond to 9,573 distinct paths, so filename reuse is possible. The separate `images` table and recipient image IDs also need review; do not assume an association from filename alone.
- The main historical image folder was copied into `storage/app/private/legacy-media-archive/images-20261116-7cd2` and all 9,683 files were verified against the source by SHA-256. Two alternate folders containing 67 and one files were separately copied and checksum-verified. They contained no filename matches for missing primary references. These local archives are not encrypted and should be protected, backed up off-machine, and kept out of the public web root.
- Following explicit user approval for private staff review, historical photo matches are enabled (`legacy.media_ready` is true) and displayed in the assessment gallery to users with `manage-people` access. Matches are marked unverified because reused filenames could show the wrong person's sensitive photo. File routes enforce authentication, authorization, a private archive path and supported image types, and responses disable caching. This is not a formal confirmation workflow and does not establish publication consent. Missing files remain unavailable. The old source media folder has not been removed or secured by this work.
- The final post-location SQL backup in `storage/app/private/legacy-backups/20261116-after-locations-d64b` was restored into `fathersheart2_finalcheck_d64b`; row totals for key tables and MySQL checksums for all 29 target tables matched. It is a local unencrypted backup and does not contain the image archive or encryption key. All restore-test databases contain sensitive information and need controlled access.
- 67 application tests passed and 10 were skipped. Existing target tables still have only one database foreign-key constraint; conversion to InnoDB did not create the others. This needs a separate integrity/constraint review.

## Historical distribution event dates and districts

The 189 event placeholders were initially created without event dates or district IDs. The old `distribution` registry was preserved separately and has 187 rows; seven names have conflicting district values, while nine physical event tables have no registry entry. Recipient record dates are not safe substitutes for event dates because the old application could overwrite them on edit.

After the user confirmed the system was idle, a fresh pre-update backup was saved to `storage/app/private/legacy-backups/20261116-before-event-locations-91e5` and restored into `fathersheart2_eventcheck_91e5`; all 29 table checksums matched. A rehearsal and the live `legacy:match-event-locations --write` then matched 170 event districts using unique exact normalized registry names and unique active district names, with no conflicting imported recipient district. Existing staff locations are never overwritten. Nineteen remain unassigned: nine without a registry entry, seven with conflicting registry districts, and three with unknown or ambiguous district names. Original registry values are shown as unverified suggestions where available. Matching is based on historical labels and still requires review.

Distribution screens now show year, month, or an explicit valid day inferred from the historical source-table name when no confirmed event date is stored. Such labels are marked unverified and do not populate `event_date`, change chronological reports, or assert that a source-name day is accurate. Names with multiple months show each named month rather than inventing a particular day or an event duration. Staff can enter an actual date after review.

## Historical team account onboarding

The historical team page now provides a table and super-admin-only actions to edit reviewed details, create a new account, link an existing non-super-admin account after explicit identity confirmation, reset a linked password, and archive or restore the historical listing. Original encrypted source rows remain immutable. Archiving a listing does not delete or disable its linked user account.

New accounts require an email and a new password of at least 12 characters. Super-admin roles are excluded from this onboarding form; no role is assigned automatically. An appropriate role with `access-admin` permission is needed for dashboard access. Password resets rotate the remember token and remove database-backed web sessions for that user; API tokens are not revoked. Existing accounts are not matched by name, username or email automatically.

Before adding the new InnoDB profile table, a private database backup was restored to `fathersheart2_teamcheck_0b76`; counts and six key table checksums matched. The migration was tested on that restored copy and then applied to `fathersheart2`. No real user accounts were created by the migration. The backup and restore copy contain sensitive data and remain local and unencrypted.
