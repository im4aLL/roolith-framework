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

Use the `trans()` helper (with `__()` as a BC alias) with a dot path key.
Missing keys or locales return null, so coalesce to a default.

```php
trans('errors.required') ?? 'This field is required'; // This field is required
__('errors.required') ?? 'This field is required'; // same via alias
```

## Setting the Language

```php
Settings::setLang('es');
Settings::getLang();
```

Note: `setLang()` writes a cookie, so the new locale applies on the next request, not the current one.

Once `es` is set, the helper reads from `lang/es/message.php`.

```php
trans('errors.required'); // este campo es requerido
```

## Adding a Language

Create a new folder under `lang` following the ISO code naming (for example `fr`).
Copy `message.php` from an existing language and translate the values while keeping the keys unchanged.
