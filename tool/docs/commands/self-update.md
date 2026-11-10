## Details

Downloads `hymn.phar` of branch `master` or of the given tag or branch from GitHub and replaces the
running file – behind a symlink, the linked file. Needs write access to that file and its folder,
usually by `sudo`.

```sh
sudo hymn self-update
sudo hymn self-update 1.0.4
hymn self-update --dry -v           # show download URL and file to replace
```

The download is saved next to the installed file, checked to be a PHAR file and then moved over the
installed file in one step. On any error, the installed file stays untouched.
