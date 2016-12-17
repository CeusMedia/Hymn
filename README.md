# Hymn
Console tool for installing Hydrogen applications.

## Commands:

### 1. Project Creation and Source Management

	- create                         Create initial Hymn file for this project
	- info                           Show project configuration from Hymn file
	- sources                        List registered library shelves
	- source-add                     Add module source of modules to Hymn file
	- source-remove KEY              Remove module source from Hymn file
	- source-enable                  ... to be implemented ...
	- source-disable                 ... to be implemented ...

### 2. Setup Configuration and Information

	- config-get KEY                 Get setting from Hymn file
	- config-set KEY [VALUE]         Enter and save setting in Hymn file
	- database-config                Enter and save database connection details
	- database-test                  Test database connection

### 3. Setup Module Management

	- config-module-add              Add a module to Hymn file
	- config-module-remove           Remove a module from Hymn file
	- config-module-get KEY          Get module setting from Hymn file
	- config-module-set KEY [VALUE]  Enter and save module setting in Hymn file
	- config-module-dump             Export current module settings into Hymn file


### 4. Module Information

	- info MODULE                    Show information about module
	- modules-available [SHELF]      List modules available in library shelve(s)
	- modules-required               List modules required for application
	- modules-installed              List modules installed within application
	- modules-updatable              List modules with available updates

### 5. Project Module Management

	- graph                          Render module relations graph
	- install [-dqv] [MODULE]        Install modules (or one specific)
	- uninstall [-dfqv] MODULE       Uninstall one specific installed module
	- update [-dqv] [MODULE]         Updated installed modules (or one specific)

### 6. Project Base Configuration

	- config-base-disable KEY        Disable an enabled setting in config.ini
	- config-base-enable KEY         Enable a disabled setting in config.ini
	- config-base-get KEY            Read setting from config.ini
	- config-base-set KEY VALUE      Save setting in config.ini

### 7. Project Database Management

	- database-clear [-fqv]          Drop database tables (force, verbose, quiet)
	- database-dump [PATH]           Export database to SQL file
	- database-load [PATH|FILE]      Import (specific or latest) SQL file

### 8. Self Management

	- help                           Show this help screen
	- version                        Show current hymn version
	- self-update                    Replace global hymn installation by latest download
	- reflect-options                Show parsable arguments and options

## Options:

	-d | --dry                       Actions are simulated only
	-f | --force                     Continue actions on error or warnings
	-q | --quiet                     Avoid any output
	-v | --verbose                   Be verbose about taken steps
	--file=[.hymn]                   Alternative path of Hymn file
	--prefix=[<%prefix%>]            Set database table prefix in dump
