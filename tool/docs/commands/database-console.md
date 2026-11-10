## Details

Runs an SQL query on the app database and shows the result as table.

```sh
hymn database-console "SELECT id, title FROM app_pages LIMIT 10"
```

Queries containing `DROP` or `DELETE` are refused. Refused by PHAR files built in production mode.
