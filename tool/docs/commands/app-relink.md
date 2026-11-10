## Details

After moving an app by hand, run this command in the new folder with the old path as argument.
Symlinks and paths in the Hymn file are changed from the old path to the current folder.
Only for apps with install type `link`.

```sh
cd /var/www/newPlace
hymn app-relink /var/www/oldPlace/
```
