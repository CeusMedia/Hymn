## Details

Imports a dump by `mysql`. Without file, `latest.sql` or else the latest dump (`dump_<date>_<time>.sql`)
in `config/sql/` or in the given folder is used. The placeholder `<%?prefix%>` is replaced by the configured prefix.
Refused by PHAR files built in production mode. See [Database](../guide/database.md#loading).
