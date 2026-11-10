## Details

Removes dumps (`dump_<date>_<time>.sql*`) in `config/sql/` or the given folder, which do not match any
rule. `--daily` is required.

```sh
hymn database-keep --daily=7 --weekly=4 --monthly=12 --yearly=5
hymn database-keep --daily=7 --dry          # show what would be removed
```

| Rule | Keeps |
|------|-------|
| `--daily=N` | the N newest dumps |
| `--weekly=N` | the N newest dumps of a Sunday |
| `--monthly=N` | the N newest dumps of the 1st of a month |
| `--yearly=N` | the N newest dumps of January 1st |

A dump is kept, if any rule matches. Refused by PHAR files built in production mode.
