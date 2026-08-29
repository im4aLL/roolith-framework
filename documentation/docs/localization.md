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

Use the `__()` helper with a dot path key.

```php
__('errors.required'); // This field is required
```

## Setting the Language

```php
Settings::setLang('es');
Settings::getLang();
```

Once `es` is set, the helper reads from `lang/es/message.php`.

```php
__('errors.required'); // este campo es requerido
```

## Adding a Language

Create a new folder under `lang` following the ISO code naming (for example `fr`).
Copy `message.php` from an existing language and translate the values while keeping the keys unchanged.
