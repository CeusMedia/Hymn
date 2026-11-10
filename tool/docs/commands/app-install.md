## Details

Without arguments, all modules of the Hymn file are installed.
Modules needed by the given modules are resolved and installed first. See [Modules](../guide/modules.md).

```sh
hymn app-install --dry              # simulate
hymn app-install                    # all modules of the Hymn file
hymn app-install UI_Bootstrap       # one module, with the modules it needs
hymn app-install --db=no            # without SQL scripts
```

Already installed modules are skipped, unless `--force` is given.

!!! note "Relations"
    Only relations marked as `<needs type="module">` in `module.xml` are installed automatically.
