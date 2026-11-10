## Details

```sh
hymn app-uninstall Info_Contact     # one module
hymn app-uninstall Info_*           # modules by wildcard
hymn app-uninstall                  # all modules - asks for confirmation, unless -f
```

Modules still needed by other installed modules are kept, unless `--force` is given.
Refused by PHAR files built in production mode.
