# Quickstart

This walk-through creates a new Hydrogen application with Hymn and installs some modules into it.

## 1. Create the project

Create a folder for the application and run `init` inside:

```sh
mkdir -p /var/www/myApp && cd /var/www/myApp
hymn init
```

Hymn asks for the basic settings – press Enter to take the default in brackets:

| Question | Default | Stored as |
|----------|---------|-----------|
| Application Title | `My Project` | `application.title` |
| Application Key Name | `MyCompany/MyProject` | used for `composer.json` |
| Installation Path | current folder | `application.uri` |
| HTTP Protocol, Host, Path | `https://`, `example.com`, `/` | `application.url` |
| Installation Type | `link` | `application.installType` – see [Modules](modules.md#install-types) |
| Installation Mode | `dev` | `application.installMode` |
| System User, Group | current user, `www-data` | `system.user`, `system.group` |

Then Hymn offers to configure the database and to create some helpful files, each only if missing:
`phpunit.xml`, `composer.json`, `config/config.ini`, `Makefile` and `.gitignore`.

The result is the [Hymn file](hymn-file.md) `.hymn` in the current folder. All further commands are
run in this folder.

## 2. Add a module source

Modules come from sources – folders with modules, like a clone of
[HydrogenModules](https://github.com/CeusMedia/HydrogenModules):

```sh
git clone https://github.com/CeusMedia/HydrogenModules.git /var/lib/HydrogenModules
hymn source-add
```

Answer with an ID (eg. `CeusMedia_Public`), the type `folder` and the path `/var/lib/HydrogenModules/src/`.

```sh
hymn source-list                         # registered sources
hymn modules-available                   # all modules of all active sources
hymn modules-search Bootstrap            # modules with "Bootstrap" in their ID
hymn module-info UI_Bootstrap            # details of a module
```

## 3. Choose modules

Add the modules your app needs to the Hymn file. Modules needed by them are installed automatically
later, so start with the top level modules:

```sh
hymn config-module-add                   # asks for the module ID and its settings
```

You can also edit `.hymn` by hand – see [Hymn file](hymn-file.md#modules).

## 4. Install

```sh
hymn app-install --dry                   # simulate first
hymn app-install                         # install all modules of the Hymn file
hymn app-status                          # check the result
```

Hymn resolves the relations between the modules, installs them in the right order, links or copies
their files, writes their configuration to `config/modules/` and runs their SQL install scripts.

To install a single module later, without adding it to the Hymn file:

```sh
hymn module-install Info_Contact
```

## 5. Keep it up to date

```sh
hymn modules-updatable                   # list outdated modules
hymn database-dump                       # backup database
hymn app-update --dry                    # simulate
hymn app-update                          # update all outdated modules
```

Continue with [Concepts](concepts.md) or look up any command in the [command reference](../commands/index.md).
