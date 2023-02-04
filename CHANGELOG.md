# Change Log

## v0.9.11
- New command: app-base-config-list
- New command: database-console
- New Hymn modes on build and run
	- Flags: dev, prod, default: prod
- Deny commands in production mode:
	- app-uninstall
	- database-clear
	- database-config
	- database-console
	- database-keep
	- database-load
- Command version shows more on verbose mode
- New CLI tool for rendering tables
- PHAR compression of on dev mode
- Going for PHPStan level 2
- New command: changelog

## v0.9.9.6
- Allow to skip database tables on dump by module config. 

## v0.9.9.5
- Apply defined module sources from hymn file on install.
- Add command init-makefile.
- Update make file template.
- Update PHPUnit and PHPStan.
- Ease framework support handling.
- Move prefix argument to command.

## v0.9.9.4
- Core:
	- Update module reader on frameworks and hooks.
	- Extend module source folder handling by source cache support.
	- Support module source index "JSON" to boost performance.
	- Support module source index "serial" to maximize performance.
- Commands:
	- Allow to create a Git ignore file on init.
	- Disable foreign key checks on database-clear.
	- Update app-move to fix module sources in hymn file.
	- Improve app-move in dry mode.
- Quality
	- Improve syntax test and verbosity.
	- Refactor client and provide memory usage tracking.
	- Add PHPStan and reach level 1.

## v0.9.9.3
- Fix dry mode app-move.
- Support deprecation of modules and rank deprecated modules lower during installations.
- Keep order of sources on loading up.
- Fix bug in PHP 8.
- Update code doc to 2021.
- Extract client configuration handling from client class.

## v0.9.9.2
- Support relations to composer packages.
- Prepare support for versioned relations.

## v0.9.9.1
- Dump app stamps as pretty JSON.
- Extend module info relations by neededBy.
- Extend modules-search verbosity.
- Extend module info relations by requiredBy.
- Create modules graph from installed modules (instead of configured modules).
- Remove deprecated classes and methods.

## v0.9.9.0
- Support self upgrade/downgrade to given version.
- Add hymn as binary in package definition.
- Fix bug in list of installed modules: shelf ID is ignored.
- Fix bug in module installer.

## v0.9.8.9
- Split module library for available and installed modules.
- Extract output methods from client to own tool class.
- Add new command database-keep to remove outdated database dumps.
- Remove symlinks on module uninstall even if target is not existing anymore.

## v0.9.8.8
- Support command specific argument options.
- Add shortcut to client output methods in all command classes (via abstract command class).
- Add new command app-clear to remove module cache, job locks and logs.
	- defines new argument option as "--age=MINUTES"
	- removes module config cache
	- removes job locks
	- removes logs (not yet implemented)

## v0.9.8.7
- Finish support of linked database resource modules in hymn file.
- Restructure CLI classes and MySQL file handling.
- Extract finding latest stamp or database file to own tool class.

## v0.9.8.6
- Extend module-info by related modules (in verbose mode).
- On module update remove modules which are not needed anymore.
- Support PHP <7.
- Add new command modules-unneeded to list modules not needed by other modules.
- Add command app-stamp-info and extract module info class from command module-info.
- Add new command stamp-diff to compare stamp against available modules.

## v0.9.8.5
- Fix bug in semantic versioning.
- Update bootstrap for PHPUnit.
- Fix bug in command for module info.
- Detect installed framework version and support module framework attribute.
- Updated make file template.
- Add option to disable interactive mode.
- Support ambiguous modules in different sources and extend module info command by verbosity.
- Details:
	- add support for sematic versioning
		- new class Hymn_Tool_Semver
		- supports version operator prefixes
		- supported operator prefixes: <,>,<>,<=,>=,!=,==,=,^
		- example: =>1.2 (same as ^1.2)
		- not supported yet: wildcards (*,?) and operator suffixes
	- add module config attribute to note supported frameworks and versions
		- attribute of XML node "module" named "frameworks"
		- format: Framework@version or Framwork1@version|Framework2@version
		- allows several frameworks, separated by colon
		- supports semantic versioning
		- defaults to: Hydrogen@<0.9
		- supported frameworks will be displayed by command module-info
	- add detection of installed Hydrogen framework
		- new class Hymn_Tool_Framework
		- available by client->getFramework()
		- lazy loading: create framework object on first call
		- has method checkModuleSupport
			- checks module framework version against installed framework version
			- will respond with exceptions if mismatch or framework config error
			- can be used by commands by try/catch
		- detected framework will be display by command app-info in verbose mode
	- add check if module supports current framework
		- compares module framework support against installed framework version
		- applies on module installation and update
		- applies only if framework could been detected
