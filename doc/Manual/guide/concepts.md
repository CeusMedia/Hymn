# Concepts

## Project and app

A **project** is described by the [Hymn file](hymn-file.md) `.hymn`: where the application lives,
which module sources are used, which modules are wanted and how they are configured.

The **app** is what Hymn builds from it: a Hydrogen application folder with installed modules, their
configuration files in `config/modules/` and their database tables.

```
myApp/
├── .hymn                       ← project: Hymn file
├── config/
│   ├── config.ini              ← base configuration of the app
│   └── modules/
│       ├── Resource_Database.xml   ← installed module, with its configuration
│       └── UI_Bootstrap.xml
├── classes/  templates/  locales/  themes/  ...   ← installed module files
└── ...
```

## Sources and modules

A **source** is a library of modules – a folder holding module folders with a `module.xml` each.
The public modules of Ceus Media are available as [HydrogenModules](https://github.com/CeusMedia/HydrogenModules).
A project can use several sources, eg. the public modules and your own. See [Sources](sources.md).

A **module** is described by its `module.xml`:

- **files** – classes, templates, locales, scripts, styles and images to install
- **configuration** – settings with types, defaults and whether they are mandatory
- **relations** – modules (and Composer packages) it needs or supports
- **SQL scripts** – for install, update (per version) and uninstall
- **hooks** – event handlers, registered in the app

Module IDs reflect the folder: module `Resource_Database` lives in `Resource/Database/` of its source.

## Relations

When a module **needs** other modules, Hymn installs them first – recursively. The needed modules
form a graph, which you can see with [`app-graph`](../commands/app-graph.md). Loops in relations are
detected and stop the installation.

Modules can also need **Composer packages** with a version constraint. Hymn checks them on install
and update – see [Versions](versions.md).

## Install type and mode

The **install type** decides how files get into the app:

| Type | Files are | Use for |
|------|-----------|---------|
| `link` | symlinked to the source | development: changes in the source are live at once |
| `copy` | copied from the source | deployment: the app is independent of the source |

The **install mode** (`dev`, `live` or `test`) describes the purpose of the installation.
Currently it only leads to a warning, if a `live` installation uses type `copy`.

## Production mode of Hymn

A PHAR file of Hymn can be built in production or development mode – see [Development](development.md).
In production mode, risky commands are refused: `database-config`, `database-load`, `database-clear`,
`database-keep`, `database-console` and `app-uninstall`.

## Simulation and safety

Most changing commands support `--dry` (`-d`): all steps are shown, but nothing is changed.
`--force` (`-f`) continues on warnings, eg. replaces existing files or uninstalls modules still needed
by others – use it with care.

## Exit codes

| Code | Meaning |
|------|---------|
| `0` | success |
| `1` | error on loading |
| `2` | error on setup, eg. invalid options or Hymn file |
| `4` | error while running a command |
| `8` | invalid input, eg. unknown module |
| `16` | error on execution, eg. a missing package |
| `32` | error on output |
