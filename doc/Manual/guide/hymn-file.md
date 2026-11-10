# Hymn file

The Hymn file `.hymn` is a JSON file in the project folder. It is created by
[`init`](../commands/init.md) and changed by many commands, but can be edited by hand as well.
Use `--file=PATH` to work with another file.

```json
{
	"application": {
		"title": "My Project",
		"url": "https://example.com/myApp/",
		"uri": "/var/www/myApp/",
		"installMode": "dev",
		"installType": "link"
	},
	"sources": {
		"CeusMedia_Public": {
			"active": true,
			"title": "Public module library maintained by Ceus Media",
			"type": "folder",
			"path": "/var/lib/HydrogenModules/src/"
		}
	},
	"modules": {
		"App_Site": {},
		"Resource_Database": {
			"config": {
				"access.username": "username",
				"access.password": "password"
			}
		}
	},
	"system": {
		"user": "www-data",
		"group": "www-data"
	},
	"database": {
		"driver": "mysql",
		"host": "localhost",
		"port": "3306",
		"name": "myapp",
		"username": "myapp",
		"password": "secret",
		"prefix": "app_"
	}
}
```

## application

| Key | Meaning |
|-----|---------|
| `title` | title of the application |
| `url` | base URL of the application |
| `uri` | absolute path of the application folder, ending with `/` |
| `installType` | `link` or `copy` – see [install types](modules.md#install-types) |
| `installMode` | `dev`, `live` or `test` – see [Concepts](concepts.md#install-type-and-mode) |

Read and change values with [`config-get`](../commands/config-get.md) and
[`config-set`](../commands/config-set.md), eg. `hymn config-set application.installType copy`.

## sources

Module sources, keyed by source ID. Manage them with [`source-add`](../commands/source-add.md),
[`source-enable`](../commands/source-enable.md), [`source-disable`](../commands/source-disable.md) and
[`source-remove`](../commands/source-remove.md).

| Key | Default | Meaning |
|-----|---------|---------|
| `type` | `folder` | type of source, only `folder` is supported |
| `path` | | absolute path of the source folder, **ending with `/`** |
| `active` | `true` | inactive sources are ignored |
| `title` | | description |

The first active source is the default source.

## modules

The modules wanted in the app, keyed by module ID. [`app-install`](../commands/app-install.md) without
arguments installs all of them, together with the modules they need.

| Key | Meaning |
|-----|---------|
| `config` | module settings as key-value pairs, applied on install and update |
| `sourceId` | source to install the module from, if the source is active – otherwise it is detected |
| `installType` | `link` or `copy` for this module, instead of `application.installType` |

```json
"modules": {
	"UI_Bootstrap": {},
	"Resource_Database": {"sourceId": "CeusMedia_Public", "config": {"access.username": "user"}},
	"My_Theme": {"sourceId": "My_Modules", "installType": "copy"}
}
```

A module without settings is written as empty object: `"UI_Bootstrap": {}`.
Keys starting with `@` are ignored, so they can be used as comments: `"@note": {}`.

Manage modules with [`config-module-add`](../commands/config-module-add.md),
[`config-module-remove`](../commands/config-module-remove.md),
[`config-module-get`](../commands/config-module-get.md) and
[`config-module-set`](../commands/config-module-set.md). To write the current settings of installed
modules back into the Hymn file, use [`app-module-config-dump`](../commands/app-module-config-dump.md).

Without `sourceId`, the source of a module is detected: the default source first, then any other
active source having the module. The older key `source` is read as well and saved as `sourceId`.

## database

Connection to the database of the app – set by [`database-config`](../commands/database-config.md)
and tested by [`database-test`](../commands/database-test.md). See [Database](database.md).

| Key | Meaning |
|-----|---------|
| `driver` | PDO driver, only `mysql` is supported |
| `host`, `port` | server, eg. `localhost` and `3306` |
| `name` | name of the database – created if missing |
| `username`, `password` | credentials |
| `prefix` | prefix of all table names, eg. `app_` |
| `modules` | modules to receive the connection settings, default `Resource_Database:access.` |

If no database `name` is set, Hymn takes the connection from the configuration of module
`Resource_Database` (`access.*` settings) instead. Without section `database`, Hymn does not touch the
database configuration of any module.

## system

| Key | Meaning |
|-----|---------|
| `user`, `group` | owner of the application files |

## paths

Folders of the app, relative to the application folder. Usually not needed: the defaults fit
Hydrogen applications, and settings `path.*` in `config/config.ini` override them.

| Key | Default |
|-----|---------|
| `config` | `config/` |
| `classes` | `classes/` |
| `templates` | `templates/` |
| `locales` | `locales/` |
| `scripts` | `scripts/` |
| `images` | `images/` |
| `themes` | `themes/` |
| `logs` | `logs/` |
| `cache` | `cache/` |
| `contents` | `contents/` |

Settings `path.*` in `config/config.ini` override these paths, but are not written into the Hymn file.

## Further sections

Sections unknown to Hymn, like your own settings or notes, are kept when commands save the Hymn file.
