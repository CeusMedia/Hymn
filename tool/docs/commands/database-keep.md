## Details

Removes dumps (`dump_<date>_<time>.sql*`) in `config/sql/` or the given folder, which do not match any
rule. `--daily` is required.

```sh
hymn database-keep --daily=7 --monthly=12 --yearly=5
hymn database-keep --daily=7 --dry          # show what would be removed
```

| Rule | Keeps |
|------|-------|
| `--daily=N` | dumps of the last N days |
| `--monthly=N` | dumps of the 1st of a month, for N months |
| `--yearly=N` | dumps of January 1st, for N years |

Refused by PHAR files built in production mode.
