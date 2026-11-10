## Details

Exports the tables of the app by `mysqldump` – only tables with the configured prefix, without
tables listed by modules in setting `onDatabaseDumpSkipTables`.

```sh
hymn database-dump                          # config/sql/dump_<date>_<time>.sql
hymn database-dump backup/                  # into an existing folder
hymn database-dump backup/manual.sql        # into a file
```

With `--dry`, the dump is created and removed again. See [Database](../guide/database.md#dumping).
