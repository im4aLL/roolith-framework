# Contributing

## Setup

```bash
composer install
npm install
```

## Checks

```bash
composer test
composer lint
composer analyse
composer audit
npm run build
php roolith route:list
```

## Docs

- Keep `ARCHITECTURE.md` canonical; `documentation/docs/architecture.md` is a short mirror with a pointer back.
- Proofread README paths (`views/`, `config/config.php`, `constant.php`) before merging docs changes.

## PR checklist

- [ ] `composer test` passes
- [ ] No `@` error suppression added (`@param` in docblocks is fine)
- [ ] New or changed PHP has param plus return types and docblocks
- [ ] Docs updated when behavior changes
