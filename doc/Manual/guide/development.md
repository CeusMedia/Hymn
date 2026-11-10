# Development

## Building the PHAR file

```sh
make create                     # run all tests, then build hymn.phar (MODE=prod or MODE=dev)
make create-phar                # build in production mode
make create-phar-dev            # build in development mode
make install-link               # symlink hymn.phar to /usr/local/bin/hymn (uses sudo)
```

| Mode | PHAR file |
|------|-----------|
| production | compressed, stripped PHP files, risky commands refused, short error output |
| development | original PHP files, not compressed, full exception traces – for debugging |

The build is done by `build/create.php --mode=prod|dev [--php=BINARY]`. It checks the syntax of all
classes, packs `CHANGELOG.md` for [`changelog`](../commands/changelog.md) and the module XML schema:
the latest one from `schema.ceusmedia.de`, or the local copy `src/classes/Structure/Module.xsd`,
if the download fails.

## Tests and static analysis

```sh
composer test:all                   # syntax, PHPStan, PHPUnit
composer test:syntax                # PHP syntax check
composer test:phpstan               # static analysis (level 6)
composer test:phpunit-units         # unit tests
composer test:phpunit-integration   # integration tests
```

Unit tests live in `test/unit/`. Integration tests in `test/integration/` run the built `hymn.phar` –
build the PHAR file first, in the version under test:

- `ModuleInstallTest` installs fixture modules of `test/fixtures/source/` into temporary apps with a
  faked Composer installation – no network, database or module library needed.
- `BasicTest` works on app templates in `test/templates/` and needs network access for Composer.

To test another PHAR file, eg. an older release, set its path in `HYMN_PHAR`:

```sh
HYMN_PHAR=/path/to/hymn.phar vendor/bin/phpunit --configuration=tool/phpunit.xml --filter ModuleInstallTest
```

## Code layout

| Path | Content |
|------|---------|
| `src/classes/Client.php` | entry point: parses arguments, reads the Hymn file, dispatches commands |
| `src/classes/Command/` | one class per command – `app-module-config-get` is `Command/App/Module/Config/Get.php` |
| `src/classes/Module/` | reading, installing, updating and uninstalling modules |
| `src/classes/Structure/` | data structures: Hymn file, module, source, stamp |
| `src/classes/Tool/` | helpers: CLI, database, XML, semantic versions, ... |
| `src/locales/` | texts in English and German, incl. help text `command/help.txt` |
| `src/baseArgumentOptions.json` | global options |
| `build/` | PHAR build |

### Adding a command

1. Create a class `Hymn_Command_<Words>` in `src/classes/Command/<Words as path>.php`, extending
   `Hymn_Command_Abstract` and implementing `Hymn_Command_Interface` with method `run()`.
2. Command specific options go into property `$argumentOptions`.
3. Add the command to the help texts `src/locales/{en,de}/command/help.txt`.
4. Optionally add details for the command reference in `tool/docs/commands/<command>.md`.

## Documentation

This documentation is built with [MkDocs](https://www.mkdocs.org/) and
[Material for MkDocs](https://squidfunk.github.io/mkdocs-material/).

```sh
composer dev:docs                # generate command reference and build site, open doc/Site/index.html
composer dev:docs-serve          # live preview on http://localhost:8000
composer dev:docs-commands       # only generate command reference (doc/Manual/commands/)
composer dev:docs-install        # install MkDocs into a Python venv (optional, else Docker is used)
```

| Path | Content |
|------|---------|
| `tool/mkdocs.yml` | configuration and navigation |
| `doc/Site/` | built HTML site, **generated** |
| `doc/Manual/` | hand-written pages (guides) and assets |
| `doc/Manual/commands/` | command reference, **generated** – do not edit |
| `tool/docs/generate-commands.php` | generator: reads the help text and the command classes |
| `tool/docs/commands/` | details, included into the page of a command (same name as command) |
| `tool/docs/docs.sh` | runs MkDocs: local installation, Python venv or Docker |

MkDocs is taken from the first available source:

1. a Python virtual environment in `tool/docs/.venv` (created by `composer dev:docs-install`),
2. `mkdocs` in `PATH` (with `mkdocs-material` installed),
3. the Docker image `squidfunk/mkdocs-material`.

!!! tip "Documenting a command"
    Section, synopsis and description of a command come from the English help text
    `src/locales/en/command/help.txt`, its options from the command class. The generator warns about
    commands missing in the help text. Add details and examples in `tool/docs/commands/<command>.md`.
