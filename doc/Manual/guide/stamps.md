# Stamps

A stamp is a snapshot of all installed modules of an app: their versions, files, configuration,
relations, hooks and SQL scripts – stored as serialized PHP file. Compare it later with the app or with the module library, to see
what has changed.

## Taking a stamp

```sh
hymn app-stamp-dump                         # into config/dumps/stamp_<date>_<time>.serial
hymn app-stamp-dump backup/                 # into another existing folder
hymn app-stamp-dump backup/stamp.serial     # into a file
hymn app-stamp-dump config/dumps/ CeusMedia_Public   # modules of one source only
```

## Comparing

| Command | Compares the stamp with |
|---------|-------------------------|
| [`app-stamp-diff`](../commands/app-stamp-diff.md) | the installed modules of the app |
| [`stamp-diff`](../commands/stamp-diff.md) | the available modules of the sources |
| [`app-stamp-info`](../commands/app-stamp-info.md) | – shows details of the stamp itself |

```sh
hymn app-stamp-diff                         # latest stamp in config/dumps/, all changes
hymn app-stamp-diff latest sql              # database changes only
hymn app-stamp-diff latest config CeusMedia_Public Resource_Database
```

Arguments are positional: stamp file or path (or `latest`), type of changes, source, module.

| Type | Changes of | Supported by |
|------|------------|--------------|
| `all` | everything (default) | all stamp commands |
| `config` | configuration | all stamp commands |
| `sql` | SQL scripts | `app-stamp-diff`, `stamp-diff`, `app-stamp-prospect` |
| `files` | files | `app-stamp-info` |
| `relations` | relations | `app-stamp-info` |
| `hooks` | hooks | `app-stamp-info` |

## Previewing updates

[`app-stamp-prospect`](../commands/app-stamp-prospect.md) needs no stamp: it shows the SQL update
scripts and configuration changes of all outdated modules – what an
[`app-update`](../commands/app-update.md) would do.

```sh
hymn app-stamp-prospect sql
hymn app-stamp-prospect config
```

## Typical workflow

```sh
hymn app-stamp-dump                 # 1. stamp before the update
hymn database-dump                  # 2. backup database
hymn app-update                     # 3. update
hymn app-stamp-diff                 # 4. what has changed?
```
