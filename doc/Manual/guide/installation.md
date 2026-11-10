# Installation

## Requirements

| Requirement | Purpose |
|-------------|---------|
| PHP **8.3** or newer | |
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
hymn self-update 1.0.x      # latest version of a branch
hymn self-update --dry -v   # show download URL and file to replace
```

`self-update` downloads `hymn.phar` from GitHub and replaces the running file – behind a symlink,
the linked file is replaced. It needs write access to that file and its folder, usually by `sudo`.
The download is checked before, so on errors the installed file stays untouched.

## Upgrading to 1.1.1

Version 1.1.1 includes the changes of 1.0.5, which fixes many errors and changes some behaviour:

- **Version constraints** of needed packages follow Composer: `^1.2` means `>=1.2.0 <2.0.0` (was
  `<1.3.0`), `~1.2` means `<2.0.0` (was: any version). Invalid constraints are errors – see [Versions](versions.md).
- **`module-install`** installs needed modules, too.
- **Module updates** stop on SQL errors, unless `--force` is given.
- **Source per module** (`sourceId`) and **install type per module** (`installType`) in the Hymn file are
  respected now – check your Hymn file, if they are set. Saving the Hymn file keeps `paths` and
  unknown sections.
- **Without section `database`** in the Hymn file, `app-install` does not install `Resource_Database`
  on its own anymore.
- **Flags** must match completely – `-dfoo` is not `-d` anymore – and short flags can be combined, like `-dv`.
- **Questions** end with an error on end of input, instead of asking forever, and `--interactive=no`
  is respected by all commands. Yes/no questions accept `y`, `yes`, `n`, `no` and Enter.
- **`database-dump`** writes the placeholder `<%?prefix%>` instead of the table prefix again, so dumps
  can be loaded into apps with other prefixes – see [Database](database.md#dumping).
- **File paths of modules** must stay within module and app folder, paths with `..` are refused.
- **`self-update`** replaces the running PHAR file and checks the download before.

See the [changelog](../changelog.md) for all changes.

## Language

Hymn speaks English and German. With the `intl` extension, the language follows the system locale
(eg. `LANG=de_DE.UTF-8`), otherwise English is used. `hymn version` shows the active language.
