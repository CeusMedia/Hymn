---
title: Home
hide:
  - navigation
  - toc
---

<div class="hero" markdown>

# CeusMedia Hymn

A console tool for applications based on the **Hydrogen framework**: describe your project in one
file, then install, update and maintain its modules, configuration and database from the command line.

[Get started](guide/quickstart.md){ .md-button .md-button--primary }
[Commands](commands/index.md){ .md-button }
[GitHub](https://github.com/CeusMedia/Hymn){ .md-button }

</div>

=== "Create a project"

    ```sh
    mkdir myApp && cd myApp
    hymn init                          # asks for title, URL, install type, database, ...
    hymn source-add                    # register a module library, eg. HydrogenModules
    hymn config-module-add             # add modules to the Hymn file
    hymn app-install                   # install all modules - with their needed modules
    ```

=== "Maintain an app"

    ```sh
    hymn app-status                    # installed modules and their state
    hymn modules-updatable             # which modules have updates?
    hymn database-dump                 # backup database first
    hymn app-update                    # update all outdated modules, incl. database changes
    ```

=== "The Hymn file"

    ```json
    {
    	"application": {
    		"title": "My Project",
    		"url": "https://example.com/",
    		"uri": "/var/www/myApp/",
    		"installMode": "dev",
    		"installType": "link"
    	},
    	"sources": {
    		"CeusMedia_Public": {"type": "folder", "path": "/path/to/HydrogenModules/src/", "active": true}
    	},
    	"modules": {
    		"Resource_Database": {"config": {"access.username": "myUser"}},
    		"UI_Bootstrap": {}
    	}
    }
    ```

## What Hymn does

<div class="grid cards" markdown>

-   :material-file-cog-outline:{ .lg .middle } __One file per project__

    ---

    The Hymn file `.hymn` holds the application settings, module sources, the wanted modules with their configuration and the database connection.

    [:octicons-arrow-right-24: Hymn file](guide/hymn-file.md)

-   :material-package-variant-closed:{ .lg .middle } __Modules with relations__

    ---

    Modules are installed with all modules they need, in the right order – by symlink for development or as copy for deployment.

    [:octicons-arrow-right-24: Modules](guide/modules.md)

-   :material-update:{ .lg .middle } __Updates with care__

    ---

    Outdated modules are updated with their files, configuration and versioned SQL update scripts. Simulate first with `--dry`.

    [:octicons-arrow-right-24: Updating](guide/modules.md#updating-modules)

-   :material-tag-check-outline:{ .lg .middle } __Version constraints__

    ---

    Modules can need Composer packages in versions like `^8.1 || ^9.0` – checked on install and update.

    [:octicons-arrow-right-24: Versions](guide/versions.md)

-   :material-database-outline:{ .lg .middle } __Database tools__

    ---

    Configure and test the connection, dump and load SQL files with table prefixes, clear tables and keep backups tidy.

    [:octicons-arrow-right-24: Database](guide/database.md)

-   :material-camera-outline:{ .lg .middle } __Stamps__

    ---

    Take a snapshot of all installed modules and compare it later with the app or with the module library.

    [:octicons-arrow-right-24: Stamps](guide/stamps.md)

</div>

## Words used

| Word | Meaning |
|------|---------|
| **Project** | blueprint of an app installation, configured by the Hymn file |
| **App** | the set of installed modules, built by the project configuration |
| **Source** | a library (folder) of installable modules, eg. [HydrogenModules](https://github.com/CeusMedia/HydrogenModules) |
| **Module** | files, configuration, SQL scripts and relations, described by a `module.xml` |

Read more in [Concepts](guide/concepts.md).

!!! info "New in version 1.0.5"
    Composer packages needed by modules are checked with version constraints like in Composer,
    `module-install` and `module-update` work on single modules, and many commands got more robust.
    Some behaviour has changed – see [Upgrading to 1.0.5](guide/installation.md#upgrading-to-105)
    and the [changelog](changelog.md).

## Requirements

- PHP 8.1 or newer with extensions `dom`, `json`, `pdo`, `simplexml`, `xml`
- Linux or another Unix-like system (symlinks, `whereis`)
- optional: `mysql` and `mysqldump` for database dumps, Graphviz `dot` for module graphs

```sh
sudo curl -LsS https://github.com/CeusMedia/Hymn/raw/master/hymn.phar -o /usr/local/bin/hymn
sudo chmod a+x /usr/local/bin/hymn
hymn version
```

License: [GPL-3.0-or-later](https://www.gnu.org/licenses/gpl-3.0.txt)
