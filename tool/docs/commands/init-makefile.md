## Details

Creates a `Makefile` for the app from the template used by [`init`](init.md).
The target file can be given as argument; if `Makefile` exists, another file name is asked for.

```sh
hymn init-makefile                  # Makefile, or asks for another name
hymn init-makefile -b -p            # keep backend and public module targets
```

Without options, targets for backend apps (`-b`), public modules (`-p`) and non-free modules (`-n`)
are removed.
