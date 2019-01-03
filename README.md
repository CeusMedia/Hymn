````
 _                 
| |_ _ _ _____ ___
|   | | |     |   |
|_|_|_  |_|_|_|_|_|
    |___|
````
hymn - Hydrogen Management

# Hymn

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/be9c412fce124997ac7f6b3bf08675be)](https://app.codacy.com/app/kriss0r/Hymn?utm_source=github.com&utm_medium=referral&utm_content=CeusMedia/Hymn&utm_campaign=Badge_Grade_Dashboard)

Console tool for installing Hydrogen applications.

## Keywords

	- Project = Blueprint for an App installation configured by Hymn file
	- App     = Set if installed modules, built or installed by project configuration
	- Source  = Shelf within the library of available modules

## Commands

### A. Configuration Management

For creating and extending a project Hymn config file, you execute these commands:

#### 1. Project Creation and Source Management

	- init                           Create initial Hymn file for this project
	- source-list                    List registered library sources
	- source-add                     Add module source of modules to Hymn file
	- source-remove KEY              Remove module source from Hymn file
	- source-enable                  ... to be implemented ...
	- source-disable                 ... to be implemented ...

#### 2. Project Setup and Information

	- info                           Show project configuration from Hymn file
	- config-get KEY                 Get setting from Hymn file
	- config-set KEY [VALUE]         Enter and save setting in Hymn file
	- database-config                Enter and save database connection details
	- database-test                  Test database connection

#### 3. Project Module Management

	- config-module-add              Add a module to Hymn file
	- config-module-remove           Remove a module from Hymn file
	- config-module-get KEY          Get module setting from Hymn file
	- config-module-set KEY [VALUE]  Enter and save module setting in Hymn file
	- config-module-dump             Export current module settings into Hymn file


#### 4. Project Module Information

	- info MODULE                    Show information about module
	- modules-available [SHELF]      List modules available in library shelve(s)
	- modules-installed              List modules installed within application
	- modules-required               List modules required for application
	- modules-search                 List modules found by name part
	- modules-updatable              List modules with available updates

### B. Application Management

Having a valid Hymn config file, you execute these commands:

#### 1. App Base Configuration Management

	- config-base-get KEY            Read setting from config.ini
	- config-base-set KEY VALUE      Save setting in config.ini
	- config-base-disable KEY        Disable an enabled setting in config.ini
	- config-base-enable KEY         Enable a disabled setting in config.ini

#### 2. App Instance & Module Management

	- app-graph                      Render module relations graph
	- app-sources                    List installed library sources
	- app-info                       (not implemented yet)
	- app-move DESTINATION [URL]     Move app to another folder (and adjust app base URL)
	- app-status                     Show status of app and its modules

#### 3. App Module Management

	- app-install [-dqv] [MODULE]    Install modules (or one specific)
	- app-uninstall [-dfqv] MODULE   Uninstall one specific installed module
	- app-update [-dqv] [MODULE]     Updated installed modules (or one specific)

#### 4. App Database Management

	- database-clear [-fqv]          Drop database tables (force, verbose, quiet)
	- database-dump [PATH]           Export database to SQL file
	- database-load [PATH|FILE]      Import (specific or latest) SQL file

### C. Additional Functions

#### 1. Self Management

	- help                           Show this help screen
	- version                        Show current hymn version
	- self-update                    Replace global hymn installation by latest download
	- reflect-options                Show parsable arguments and options

## Options

	-d | --dry                       Actions are simulated only
	-f | --force                     Continue actions on error or warnings
	-q | --quiet                     Avoid any output
	-v | --verbose                   Be verbose about taken steps
	--file=[.hymn]                   Alternative path of Hymn file
	--prefix=[<%prefix%>]            Set database table prefix in dump

## Commands of `make`

### create / create-phar

Creates Phar file `hymn.phar` in default LIVE mode.

The resulting file:

- contains reduced PHP files (e.G. comments are stipped out)
- is compressed
- will show bogus info on internal exceptions (use DEV mode for debugging)

### create-phar-dev

Creates Phar file `hymn.phar` in DEV mode.

The resulting file:

- contains original PHP files
- is not compressed
- is usable for debugging internal errors

### install

Will use `install-link` to symlink hymn.phar to global scope.

### install-copy

Installs `hymn.phar` to global scope by creating a copy in `/usr/local/bin/hymn`.

Will:

- remove `/usr/local/bin/hymn`
- copy local `hymn.phar` to `/usr/local/bin/hymn` 

### install-link

Installs `hymn.phar` to global scope by creating a symlink in `/usr/local/bin/hymn`.

Will:

- remove `/usr/local/bin/hymn`
- link local `hymn.phar` to `/usr/local/bin/hymn` 

### test-syntax

Will test syntax of local PHP class files using `hymn test-syntax`. 

### test-units

Will run PhpUnit (if installed) to test source files.

### uninstall

Will remove global file or symlink `/usr/local/bin/hymn`.

### update

Applies remote updates to local installation.

Will:

- print out current version
- revert local changes to `hymn.phar`
- stash local changes
- fetch updates
- merge updates and local commits using `rebase`
- apply stashed local changes
- make create-phar


## Appendix

### Disclaimer

Logo rendered using [patorjk.com](http://patorjk.com/software/taag/#p=display&f=Rectangles&t=hymn)

