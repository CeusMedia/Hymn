## Details

```sh
hymn app-clear                      # module cache and locks
hymn app-clear cache                # module cache: config/modules.cache.serial
hymn app-clear locks --age=60       # job locks in config/locks/ older than 60 minutes
```

!!! note
    Type `logs` is listed, but not implemented yet.
