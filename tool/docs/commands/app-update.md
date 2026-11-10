## Details

Without arguments, all outdated modules are updated and modules of the Hymn file not installed yet
are installed. See [Updating modules](../guide/modules.md#updating-modules).

```sh
hymn app-update --dry               # simulate
hymn app-update                     # all outdated modules
hymn app-update UI_Bootstrap -f     # reinstall a module, even if not outdated
```

!!! tip
    Dump the database before: `hymn database-dump`. Preview changes by
    [`app-stamp-prospect`](app-stamp-prospect.md).
