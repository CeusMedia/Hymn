## Details

Updates one installed module from the source it was installed from.
See [Updating modules](../guide/modules.md#updating-modules).

```sh
hymn module-update UI_Bootstrap
hymn module-update --dry UI_Bootstrap         # simulate
hymn module-update --force UI_Bootstrap       # reinstall, even if not outdated
```

Modules newly needed by the new version are installed, modules not needed anymore – and not needed
by other modules – are uninstalled. Fails if the module is not outdated (unless `--force`) or not
available in its source anymore.
