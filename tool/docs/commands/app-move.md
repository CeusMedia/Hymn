## Details

Moves the app folder to a new absolute path, which must not exist yet, and adjusts:

- `application.uri`, `application.url` and source paths in the Hymn file,
- `app.base.url` in `config/config.ini`, if given and set,
- symlinks of linked module files.

```sh
hymn app-move /var/www/newPlace https://example.com/newPlace/ --dry
```
