## Details

Exports the tables of the app by `mysqldump` – only tables with the configured prefix, without
tables listed by modules in setting `onDatabaseDumpSkipTables`.

```sh
hymn database-dump                          # config/sql/dump_<date>_<time>.sql
hymn database-dump backup/                  # into a folder, created if missing
hymn database-dump backup/manual.sql        # into a file
hymn database-dump --prefix=test_           # rename tables in dump to prefix test_
```

The table prefix is replaced by the placeholder `<%?prefix%>` in the dump, so the dump can be loaded
into apps with other prefixes by [`database-load`](database-load.md). With `--prefix=PREFIX`, the given
prefix is written instead. With `--dry`, the dump is created and removed again.
See [Database](../guide/database.md#dumping).
