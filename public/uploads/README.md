Place web-accessible uploads here, outside the Vite build output.

`npm run build` wipes only `assets/build/` (`emptyOutDir`), so files
under `public/uploads/` survive rebuilds. `publicDir: false` in
vite.config.mjs disables Vite's default `public/` copy, so uploads are
never duplicated into `assets/build/uploads/`; the files here are served
by Apache/PHP from the docroot, not by Vite.
Prefer `storage/` (outside the docroot) for private files.
