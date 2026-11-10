# Modules

## Installing modules

```sh
hymn app-install                          # all modules of the Hymn file
hymn app-install UI_Bootstrap Info_Pages  # specific modules
hymn module-install Info_Contact          # one module, optionally from a given source
```

For each module, Hymn

1. resolves the modules it **needs** and installs them first, in the right order,
2. checks the [Composer packages](versions.md) it needs,
3. links or copies its **files** into the app,
4. writes its **configuration** to `config/modules/<ID>.xml` – with settings from the Hymn file;
   missing mandatory values are asked for,
5. runs its **SQL install scripts**, with the table prefix of the [database](database.md).

Modules already installed are skipped, unless `--force` is given.
Use `--dry` to see what would happen.

!!! note "`app-install` and `module-install`"
    `app-install` works on the modules of the Hymn file and installs needed modules as well.
    `module-install` installs exactly one module, which must not be installed yet – needed modules
    are not installed automatically.

### Install types

| Type | Files are | Note |
|------|-----------|------|
| `link` | symlinked from the source | existing files in the app are kept, unless `--force` |
| `copy` | copied from the source | existing files are overwritten |

The type is set by `application.installType` in the [Hymn file](hymn-file.md).

### Where files go

| Files of module | Target in app |
|-----------------|---------------|
| classes | `classes/` |
| templates | `templates/` |
| locales | path `locales` |
| scripts | path `scripts` |
| styles | `themes/<theme>/css/` – theme `common`, primer or the app theme |
| images | path `images` or `themes/<theme>/img/` |
| other files | relative to the app folder |

### Database changes only, or none

```sh
hymn app-install --db=no       # files and configuration, no SQL
hymn app-install --db=only     # configuration and SQL, no files
```

## Configuring modules

```sh
hymn app-module-config-get Resource_Database.access.host
hymn app-module-config-set Resource_Database.access.host db.example.com
hymn app-module-reconfigure Resource_Database      # go through all settings interactively
hymn app-module-config-dump                        # write settings back into the Hymn file
```

Settings of installed modules live in `config/modules/<ID>.xml`. Changes there are kept on updates,
since Hymn merges them with new settings of the module.

## Updating modules

```sh
hymn modules-updatable                 # outdated modules: installed vs. available version
hymn app-update --dry                  # simulate
hymn app-update                        # all outdated modules
hymn app-update UI_Bootstrap           # specific modules
hymn module-update UI_Bootstrap        # one module
```

On update, Hymn

1. installs modules newly needed by the module,
2. replaces the files of the module,
3. merges the configuration – for changed settings you can decide to keep or take over values,
4. runs all SQL update scripts with a version newer than the installed version.

`app-update` also installs modules of the Hymn file not installed yet.
Use `--force` to reinstall a module which is not outdated.

!!! tip
    Dump the database before updating: `hymn database-dump`. To preview the SQL and configuration
    changes of all outdated modules, use [`app-stamp-prospect`](../commands/app-stamp-prospect.md).

## Uninstalling modules

```sh
hymn app-uninstall Info_Contact        # one module
hymn app-uninstall                     # all modules – asks for confirmation
```

Modules needed by other installed modules are not uninstalled, unless `--force` is given.
Files and the module configuration are removed and the SQL uninstall scripts are run.

## Inspecting the app

| Command | Shows |
|---------|-------|
| [`app-info`](../commands/app-info.md) | setup of the app |
| [`app-status`](../commands/app-status.md) | installed modules and their state |
| [`modules-installed`](../commands/modules-installed.md) | installed modules |
| [`modules-required`](../commands/modules-required.md) | modules required by the Hymn file |
| [`modules-unneeded`](../commands/modules-unneeded.md) | installed modules not needed by others |
| [`app-hooks`](../commands/app-hooks.md) | event hooks of installed modules |
| [`app-graph`](../commands/app-graph.md) | graph of module relations as image |
