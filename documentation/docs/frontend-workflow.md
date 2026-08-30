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

Admin entries are installed by the installer under `source/scss/admin/admin.scss` and `source/js/admin/admin.js`.
The Vite config picks them up automatically when these files exist.

## Development with hot module replacement

Set `viteDevServer` to `http://localhost:5173` in `config/config.php` and run

```bash
npm run dev
```

The PHP app (default `http://localhost:8080`) then loads styles and scripts from the Vite dev server with hot module replacement.
Views use the `viteCss()` and `viteJs()` helpers which switch between dev server urls and built assets automatically.

You can also browse the whole site through `http://localhost:5173` because the dev server proxies the PHP app and reloads the page when PHP files change.

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

This creates minified assets in the `assets` folder, for example `assets/css/app.css` and `assets/js/app.js`.
