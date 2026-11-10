# Database

Many modules bring database tables – created by SQL scripts on install, changed on update and removed
on uninstall. Hymn needs a connection for that, and offers commands to back up and restore the database.
Only MySQL and MariaDB are supported.

## Connection

```sh
hymn database-config        # asks for driver, host, port, name, user, password, prefix
hymn database-test          # test the connection
```

`database-config` asks until a connection can be made – a missing database is created – and stores
the settings in section `database` of the [Hymn file](hymn-file.md#database). They are also passed to
the configuration of module `Resource_Database` (settings `access.*`), so the app uses the same connection.

### Table prefix

All tables of the app can share a prefix, eg. `app_`. In SQL scripts of modules, the placeholder
`<%?prefix%>` stands for it:

```sql
CREATE TABLE `<%?prefix%>users` ( ... );
```

## Dumping

```sh
hymn database-dump                          # into config/sql/dump_<date>_<time>.sql
hymn database-dump backup/                  # into another folder (must exist)
hymn database-dump backup/before-update.sql # into a file
```

The dump is made by `mysqldump` and contains the tables with the configured prefix only.
Tables listed by modules in setting `onDatabaseDumpSkipTables` are left out.

## Loading

```sh
hymn database-load                          # latest dump in config/sql/
hymn database-load backup/                  # latest dump in a folder
hymn database-load backup/before-update.sql # a specific file
```

Without file, a dump named `latest.sql` is taken, if existing – otherwise the latest dump by its file
name `dump_<date>_<time>.sql`. Before import by `mysql`, the placeholder `<%?prefix%>` is replaced by
the configured table prefix.

## Clearing

```sh
hymn database-clear         # drop all tables with the prefix - asks for confirmation
hymn database-clear -f      # without confirmation
```

## Keeping backups tidy

[`database-keep`](../commands/database-keep.md) removes old dumps, keeping only those matching rules:

```sh
hymn database-keep --daily=7 --monthly=12 --yearly=5
```

| Rule | Keeps |
|------|-------|
| `--daily=N` | dumps of the last N days – required |
| `--monthly=N` | dumps of the 1st of a month, for N months |
| `--yearly=N` | dumps of January 1st, for N years |

## Console

```sh
hymn database-console "SELECT COUNT(*) FROM app_users"
```

Runs a query and shows the result as table. Queries with `DROP` or `DELETE` are refused.

!!! warning "Production mode"
    A PHAR file of Hymn built in production mode refuses `database-config`, `database-load`,
    `database-clear`, `database-keep` and `database-console`.

## Without database

To install or update modules without touching the database, add `--db=no`.
To run only the database part, eg. after restoring files, use `--db=only`.
