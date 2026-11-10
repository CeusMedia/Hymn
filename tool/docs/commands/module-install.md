## Details

Installs one module, which is not installed yet – from the given source or from the
[detected source](../guide/sources.md#several-sources). Modules needed by the module are installed
first, if not installed yet – in the right order, with their sources detected the same way.

```sh
hymn module-install Info_Contact
hymn module-install Info_Contact My_Modules
hymn module-install --dry -v Info_Contact     # show which modules would be installed
```

Unlike [`app-install`](app-install.md), the module is not added to the Hymn file.
