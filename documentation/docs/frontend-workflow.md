# Frontend Workflow

The framework ships with a Vite based workflow for SCSS and JavaScript.

## Setup

Install the node dependencies from the project root.

```bash
npm install
```

## Entries

Add your styles and scripts to the entry files.

```text
source/scss/app.scss
source/js/app.js
```

Admin entries ship via the CMS release asset under `source/scss/admin/admin.scss` and `source/js/admin/admin.js` (see `cms-installer.md`).
The Vite config picks them up automatically when these files exist.

## Development with hot module replacement

Set `viteDevServer` to `http://localhost:5173` in `config/config.php` and run

```bash
npm run dev
```

The PHP app (default `http://localhost:8080`) then loads styles and scripts from the Vite dev server with hot module replacement.
Views use the `viteCss()` and `viteJs()` helpers which switch between dev server urls and built assets automatically. Both helpers emit the HMR client tag once per page, so JS-only pages also get HMR, and all URLs are escaped.

You can also browse the whole site through `http://localhost:5173` because the dev server proxies the PHP app and reloads the page when PHP files change.

### Vite proxy limits

The dev server proxies everything except Vite internals to PHP on `:8080`:

```
^/(?!@vite|@id|@fs|node_modules|source|__open-in-editor).*$
```

- Bypassed (served by Vite): `@vite/client`, `@id/*`, `@fs/*`, `node_modules/*`, `source/*`, `__open-in-editor`.
- Proxied (served by PHP): all pages, API routes, form posts, websockets. Sessions and cookies keep working because the browser stays on one origin.
- Do not add API or websocket bypasses without re-testing PHP sessions plus HMR together: log in via `:8080`, browse via `:5173`, confirm the session persists and HMR still reloads on JS plus PHP edits.

## Development without the dev server

If you prefer built files on disk, run

```bash
npm run watch
```

Keep `viteDevServer` empty in this mode.

## Production Build

```bash
npm run build
```

This creates minified, content-hashed assets in the `assets/build` folder, for example `assets/build/css/app-[hash].css` and `assets/build/js/app-[hash].js` plus `assets/build/.vite/manifest.json`. Views resolve the hashed names via the manifest and fall back to stable paths plus `?v=version` when no manifest exists.

Uploads must live outside the build output: `public/uploads/` for web-accessible files (fixture `public/uploads/.keep-me.txt` survives rebuilds because only `assets/build` is wiped) or `storage/` outside the docroot for private files. Never store uploads under `assets/`.
