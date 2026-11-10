# Sources

A source is a folder holding modules. Each module lives in a folder named after its ID – underscores
become folder separators – and is described by a `module.xml`:

```
HydrogenModules/src/
├── Resource/
│   └── Database/
│       ├── module.xml          ← module Resource_Database
│       ├── classes/
│       └── ...
└── UI/
    └── Bootstrap/
        └── module.xml          ← module UI_Bootstrap
```

## Adding a source

```sh
hymn source-add
```

| Question | Example |
|----------|---------|
| Source ID | `CeusMedia_Public` |
| Source type | `folder` – the only type supported |
| Source path | `/var/lib/HydrogenModules/src/` – must exist |
| Source description | optional, defaults to the ID |

The source is stored with its real path and activated. List sources with
[`source-list`](../commands/source-list.md).

## Several sources

A project can use several sources, eg. the public modules and a source with your own modules.
When a module is installed, its source is detected:

1. the default source – the first active source in the Hymn file – if it has the module,
2. otherwise the first other active source having the module.

Install a module from a specific source with `hymn module-install MODULE SOURCE`.
Disable a source temporarily with [`source-disable`](../commands/source-disable.md).

## Exploring modules

```sh
hymn modules-available                  # all modules of all active sources
hymn modules-available CeusMedia_Public # modules of one source
hymn modules-search Shop                # modules with "Shop" in their ID
hymn module-info Shop_Bridge            # details: version, files, config, relations
hymn module-info -v Shop_Bridge         # even more details
```

## Validating

[`source-validate`](../commands/source-validate.md) checks the `module.xml` files of all modules in a
source against the module XML schema – useful when developing own modules.

## Source index

[`source-index-create`](../commands/source-index-create.md) writes an index of all modules into the
source folder – `index.json` and `index.serial` – with meta data from an optional `.index.ini`:

```ini
id			= "CeusMedia_Public"
title		= "Public module library maintained by Ceus Media"
url			= "https://github.com/CeusMedia/HydrogenModules"
description	= "..."
```

Show it with [`source-index`](../commands/source-index.md) and remove it with
[`source-index-clear`](../commands/source-index-clear.md).

!!! note
    Hymn itself reads modules from the folders, not from the index.
