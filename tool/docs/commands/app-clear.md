## Details

```sh
hymn app-clear                      # all: module cache, locks and logs
hymn app-clear cache                # module cache: config/modules.cache.serial
hymn app-clear locks --age=60       # job locks in config/locks/ older than 60 minutes
hymn app-clear logs                 # log files in path logs, eg. logs/
hymn app-clear -dv                  # show what would be removed
```

Locks are files `*.lock`, logs are files `*.log`, including rotated ones like `*.log.1` or `*.log.2.gz`.
Other files, like `.htaccess`, are kept. With `--age`, only files older than the given minutes are removed.
