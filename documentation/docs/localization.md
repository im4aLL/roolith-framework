# Localization

Localization files live in the `lang` folder.
Each language has its own folder with a `message.php` file.

```text
lang/
├── en/
│   └── message.php
└── es/
    └── message.php
```

## Getting a Message

Use the `trans()` helper (with `__()` as a BC alias) with a dot path key. `Translator::trans(string $key): mixed` calls `Str::getMessage()` which resolves `Arr::get()` dot notation against the active locale catalog. Missing keys or locales return null, so coalesce to a default.

```php
trans('errors.required') ?? 'This field is required'; // This field is required
__('errors.required') ?? 'This field is required'; // same via alias
```

## Locale Validation

`Language::sanitizeLang(mixed $lang): string` only accepts `/^[a-z]{2}(-[A-Z]{2})?$/` values that have a matching directory from `Language::allowedLocales(): array` which scans `lang/` once per process; anything else falls back to `Language::FALLBACK_LANG` (`en`) so traversal payloads like `../` never reach the loader. `getMessages(?string $lang = null): array` loads the sanitized catalog and falls back to `en` (default `Settings::LANG_DEFAULT`), returning `[]` when even the fallback is missing.

## Setting the Language

```php
Settings::setLang('es');
Settings::getLang();
```

Note: `setLang(string $lang): bool` sanitizes via `Language::sanitizeLang()` then writes the `lang` cookie for 1 month, so the new locale applies on the next request, not the current one. `getLang(): string` reads that cookie through the allowlist and falls back to `Settings::defaultLocale()` which resolves Config `locale`, then Env `APP_LOCALE`, then `en`.

Once `es` is set, the helper reads from `lang/es/message.php`.

```php
trans('errors.required'); // este campo es requerido
```

## Adding a Language

Create a new folder under `lang` following the ISO code naming (for example `fr`).
Copy `message.php` from an existing language and translate the values while keeping the keys unchanged.
