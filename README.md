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

	- init                                        Create initial Hymn file for this project
	- source-list                                 List library sources registered in Hymn file
	- source-add                                  Add module source of modules to Hymn file
	- source-remove KEY                           Remove module source from Hymn file
	- source-enable [-dfqv] SOURCE                Enable source in Hymn file
	- source-disable [-dfqv] SOURCE               Disable source in Hymn file

#### 2. Project Setup and Information

	- config-get KEY                              Get setting from Hymn file
	- config-set KEY [VALUE]                      Enter and save setting in Hymn file
	- database-config                             Enter and save database connection details
	- database-test                               Test database connection

#### 3. Project Module Management

	- config-module-add                           Add a module to Hymn file
	- config-module-remove                        Remove a module from Hymn file
	- config-module-get MODULE.KEY                Get module setting from Hymn file
	- config-module-set MODULE.KEY [VALUE]        Enter and save module setting in Hymn file


#### 4. Project Module Information

	- module-info [-v] MODULE [SOURCE]            Show information about module (in library source)
	- modules-available [SOURCE]                  List modules available (in library source)
	- modules-installed [SOURCE]                  List modules installed within application (from library source)
	- modules-required                            List modules required for application
	- modules-search NAME [SOURCE]                List modules found by name part (within library source)
	- modules-updatable                           List modules with available updates
	- modules-unneeded                            List modules not needed by other modules

### B. Application Management

Having a valid Hymn config file, you execute these commands:

#### 1. App Base Configuration Management

	- app-base-config-disable [-dqv] KEY          Disable an enabled setting in config.ini
	- app-base-config-enable [-dqv] KEY           Enable a disabled setting in config.ini
	- app-base-config-get KEY                     Read setting from config.ini
	- app-base-config-set [-dqv] KEY VALUE        Save setting in config.ini

#### 2. App Instance & Module Management

	- app-info [-v]                               Show app setup
	- app-status [-v]                             Show status of app and its modules
	- app-graph                                   Render module relations graph (see config/modules.graph.png)
	- app-sources                                 List installed library sources
	- app-move DESTINATION [URL]                  Move app to another folder (and adjust app base URL)

#### 3. App Module Management

	- app-install [-dqv] [MODULE+]                Install specific available module(s) (or all)
	- app-uninstall [-dfqv] [MODULE+]             Uninstall specific installed module(s) (or all)
	- app-update [-dqv] [MODULE+]                 Updated specific installed modules(s) (or all)
	- app-module-config-get MODULE.KEY            Get config value of installed module by config key
	- app-module-config-set MODULE.KEY VALUE      Set config value of installed module by config key
	- app-module-config-dump [--file=.hymn]       Export current module settings into Hymn file
	- app-module-reconfigure [-dqv] MODULE        Configure installed module

#### 4. App Database Management

	- database-clear [-dfqv]                      Drop database tables
	- database-dump [PATH]                        Export database to SQL file
	- database-load [PATH|FILE]                   Import (specific or latest) SQL file
	- database-keep [KEEP_RULES]                  Remove database dumps not meeting the keep rules

	Keep Rules:
	--daily=[DAYS]             Number of daily dumps to keep
	--monthly=[MONTHS]         Number of monthly dumps to keep
	--yearly=[YEARS]           Number of yearly dumps to keep

#### 5. App Stamp Management
------------------------------------------------------------------------------
	- app-stamp-dump [SOURCE]                              Export current module settings into a stamp file
	- app-stamp-diff [PATH|FILE] [TYPE] [SOURCE] [MODULE]  Show changes between installed modules and stamp
	- app-stamp-load [PATH|FILE] [TYPE] [SOURCE] [MODULE]  Restore module settings of stamp (not implemented yet)
	- app-stamp-prospect TYPE [SOURCE]                     Show changes when updating outdated modules
	- app-stamp-info [PATH|FILE] [TYPE*] [SOURCE] [MODULE] Display details of stamp

### C. Additional Functions

#### 1. Self Management

	- help                           Show this help screen
	- version                        Show current hymn version
	- self-update [-dv] [VERSION]    Replace hymn binary by master or given version
	- test-syntax [-rqv] [PATH]      Test syntax of Hymn PHP classes

#### 2. Stamp Management

	- stamp-diff [PATH|FILE] [TYPE] [SOURCE] [MODULE]  Show changes between available modules and stamp

## Options

	-d | --dry                 Actions are simulated only
	-f | --force               Continue actions on error or warnings
	-q | --quiet               Avoid any output
	-v | --verbose             Be verbose about taken steps
	-r | --recursive           Perform file actions recursively (used by test-syntax only)
	--file=[.hymn]             Alternative path of Hymn file
	--prefix=[<%prefix%>]      Set database table prefix in dump
	--db=[yes,no,only]         Switch to enable changes in database (default: yes)

## Commands of `make`

	create [MODE=(prod|dev)]   Create Phar file `hymn.phar` in PROD or DEV mode (default: PROD)
	create-phar                Create Phar file `hymn.phar` in PROD mode (default)
	create-phar-dev            Create Phar file `hymn.phar` in DEV mode
	install                    Alias for install-link
	install-copy               Install hymn.phar as copy in /usr/local/bin/hymn
	install-link               Install hymn.phar as symlink in /usr/local/bin/hymn
	test-syntax                Test syntax of local PHP class files (using hymn test-syntax)
	test-units                 Run PhpUnit (if installed) to test source files
	uninstall                  Remove global file or symlink /usr/local/bin/hymn
	update                     Apply remote updates to local installation

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
