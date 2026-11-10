# App configuration

Besides the [Hymn file](hymn-file.md) and the settings of each module, a Hydrogen application has a
base configuration: `config/config.ini`. `hymn init` can create it from a template.

```ini
app.name				= "My Project"
app.version				= "0.1"
;app.base.url			= "http://example.com/"

path.locales			= "locales/"
path.themes				= "themes/"

layout.primer			= ""
layout.theme			= "custom"

;system.cache.modules	= no
```

## Reading and changing settings

```sh
hymn app-base-config-list                       # all settings
hymn app-base-config-get app.base.url           # one setting
hymn app-base-config-set app.version 0.2        # change a setting
hymn app-base-config-set app.version            # asks for the value
```

Unknown keys are refused by `app-base-config-set`, unless `--force` is given.

## Enabling and disabling settings

A setting is disabled by a leading semicolon – it stays in the file, but is not active:

```sh
hymn app-base-config-enable system.cache.modules    # ;system.cache.modules → system.cache.modules
hymn app-base-config-disable system.cache.modules   # system.cache.modules → ;system.cache.modules
```

## Values

| Written as | Read as |
|------------|---------|
| `yes`, `true` | boolean true |
| `no`, `false` | boolean false |
| `null` | null |
| `"text"` | text, quotes are removed |

## Paths and layout

Settings starting with `path.` define the folders of the app, eg. `path.locales = "locales/"`, and
are used by Hymn when installing module files. Settings starting with `layout.` define the primer and
theme, which decide where styles and images of modules go.

## Cache, locks and logs

```sh
hymn app-clear                  # all: module cache and locks
hymn app-clear cache            # module cache config/modules.cache.serial
hymn app-clear locks --age=60   # job locks older than 60 minutes
hymn app-module-config-uncache  # remove app module cache
```

## Moving an app

```sh
hymn app-move /var/www/newPlace https://example.com/newPlace/
```

[`app-move`](../commands/app-move.md) moves the app folder, adjusts the Hymn file, the base URL in
`config.ini` and the symlinks of linked modules. After moving an app by hand, fix the symlinks with
[`app-relink`](../commands/app-relink.md) – called in the new folder with the old path as argument.
