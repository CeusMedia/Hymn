## Details

Installs one module, which is not installed yet – from the given source or from the
[detected source](../guide/sources.md#several-sources).

```sh
hymn module-install Info_Contact
hymn module-install Info_Contact My_Modules
```

Unlike [`app-install`](app-install.md), modules needed by the module are **not** installed
automatically – install them before, or use `app-install`.
