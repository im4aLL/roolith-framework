# Frontend Workflow

The framework ships with a Gulp based workflow for SCSS and JavaScript.

## Setup

Install the node dependencies from the project root.

```bash
npm install
```

Before starting, open `gulpfile.js` and update the BrowserSync options for your setup.
Especially change the vhost defined as `local.roolith-framework.me`.

## Development

```bash
npm start
```

This compiles SCSS and JS and starts watching for changes.

## Writing Code

Add your styles and scripts to the entry files.

```text
source/scss/app.scss
source/js/app.js
```

## Production Build

```bash
npm run build
```

This creates minified `min.css` and `min.js` files for production.

## Admin Assets

If you use the CMS feature there is a separate workflow for admin assets.

```bash
npm run watch:admin
npm run build:admin
```
