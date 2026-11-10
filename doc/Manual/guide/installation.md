# Installation

## Requirements

| Requirement | Purpose |
|-------------|---------|
| PHP **8.1** or newer | |
| `ext-dom`, `ext-json`, `ext-pdo`, `ext-simplexml`, `ext-xml` | reading modules and Hymn file, database access |
| `ext-pdo_mysql` | database commands – only MySQL/MariaDB is supported |
| `ext-intl` | optional: language detection for German output |
| `mysql` and `mysqldump` command line clients | only for [database dumps](database.md) |
| Graphviz (`dot`) | only for [`app-graph`](../commands/app-graph.md) |
| `composer` | only for `hymn init`, if a `composer.json` should be created |

## As PHAR file

Hymn is distributed as a single PHAR file. Download it and make it executable:

```sh
sudo curl -LsS https://github.com/CeusMedia/Hymn/raw/master/hymn.phar -o /usr/local/bin/hymn
sudo chmod a+x /usr/local/bin/hymn
```

Afterwards the command `hymn` is available everywhere. Check it by showing the version:

```sh
hymn version
```

## From the repository

```sh
git clone https://github.com/CeusMedia/Hymn.git
cd Hymn
make install-link       # symlink hymn.phar to /usr/local/bin/hymn (uses sudo)
```

Use `make install-copy` to copy the file instead and `make uninstall` to remove it again.
To build the PHAR file yourself, see [Development](development.md).

## Updating Hymn

```sh
hymn self-update            # latest version of branch master
hymn self-update 1.0.4      # a tagged version
```

`self-update` downloads `hymn.phar` from GitHub and replaces the file found by `whereis hymn`,
so it needs write access to that file – usually by `sudo`.

!!! warning "Version format"
    The version argument must consist of digits and dots only. Versions with a suffix, like `1.0.5c`,
    are rejected – use the repository and `make` for those.

## Language

Hymn speaks English and German. With the `intl` extension, the language follows the system locale
(eg. `LANG=de_DE.UTF-8`), otherwise English is used. `hymn version` shows the active language.
