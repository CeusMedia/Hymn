## Details

Asks for the application settings and writes the [Hymn file](../guide/hymn-file.md). Optionally
configures the database and creates these files – each only if missing:

| File | Content |
|------|---------|
| `phpunit.xml` | PHPUnit configuration, asks for test folder and result path, optionally with `test/bootstrap.php` |
| `composer.json` | by `composer init`, asks for author name and e-mail |
| `config/config.ini` | base configuration of the app, asks for version and language, with `config/.htaccess` |
| `Makefile` | targets for the app, eg. `make set-permissions` |
| `.gitignore` | ignores `vendor/`, logs, local configuration and dot files – incl. the Hymn file |

The Hymn file has no sources yet – add them by [`source-add`](source-add.md).
See [Quickstart](../guide/quickstart.md) for a walk-through.
