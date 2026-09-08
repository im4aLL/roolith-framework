# Recipes

Short end-to-end guides for common tasks. Each recipe shows one working approach you can copy into your app.

- [Middleware](/middleware) - run a check before a route handler runs.
  Use it to redirect guests to login, verify CSRF tokens on forms, or throttle brute-force logins.
- [Extending a Model](/extending-a-model) - encapsulate business logic in models.
  Use it to keep controllers thin with query helpers, computed fields, and role checks.
- [Lazy Load Models](/lazy-load-models) - attach related models with `App\Core\LazyLoad`.
  Use it to avoid N+1 queries when listing parents with their relations.
- [File Upload](/file-upload) - handle uploads and validation.
  Use it to accept files through `Request::file()`, validate type and size, and move them with `FS`.
- [Sending Email](/sending-email) - send mail with PHPMailer.
  Use it to wrap SMTP in a small fluent `Mailer` utility so controllers stay clean.
- [Using Dot ENV](/using-dot-env) - load environment variables from `.env` with vlucas/phpdotenv.
  Use it to keep secrets and per-environment settings out of tracked config.
- [Date Helpers](/date-helpers) - work with dates using Carbon.
  Use it for readable date math, formatting, and cookie-friendly expirations.
- [Custom View Engine](/custom-view-engine) - swap the template engine for Mustache or Twig.
  Use it when you want a different templating syntax without changing controller call sites.
- [Custom ORM (Doctrine)](/custom-orm) - replace the default database layer with Doctrine ORM.
  Use it when you need entities, repositories, and migrations from the Doctrine ecosystem.
- [Custom ORM (Cycle)](/cycle-orm) - replace the default database layer with Cycle ORM DataMapper.
  Use it when you prefer DataMapper-style entities with Cycle's schema and repository support.
