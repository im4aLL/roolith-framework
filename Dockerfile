FROM php:8.2-apache

# Verify locally with: composer check-platform-reqs
# HTTPS is terminated outside this container (load balancer or reverse proxy);
# the image only serves plain HTTP on port 80.
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libzip-dev curl \
    && docker-php-ext-install pdo_mysql mysqli intl zip opcache \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

RUN echo '<Directory /var/www/html>\n    AllowOverride All\n</Directory>' \
    > /etc/apache2/conf-available/allow-override.conf \
    && a2enconf allow-override

# Minimal Apache hardening for prod. Hides version banners, disables
# TRACE, and strips ETags. App security headers (CSP, nosniff, frame, HSTS)
# are sent by PHP via System::sendSecurityHeaders(); this only covers the
# server layer. Caching plus compression below is for static assets only.
RUN printf 'ServerTokens Prod\nServerSignature Off\nTraceEnable Off\nFileETag None\n' \
    > /etc/apache2/conf-available/security-hardening.conf \
    && a2enconf security-hardening

RUN printf '<IfModule mod_expires.c>\n    ExpiresActive On\n    ExpiresByType text/css "access plus 1 year"\n    ExpiresByType application/javascript "access plus 1 year"\n    ExpiresByType image/svg+xml "access plus 1 year"\n</IfModule>\n<IfModule mod_deflate.c>\n    AddOutputFilterByType DEFLATE text/css application/javascript image/svg+xml\n</IfModule>\n' \
    > /etc/apache2/conf-available/cache-compression.conf \
    && a2enconf cache-compression

# Prod parity: copy the app so docker-compose.prod.yml runs without a bind
# mount. Dev compose bind-mounts ./ over this copy for live reload.
COPY . /var/www/html
# Fail fast when `npm run build` was skipped before `docker build`: without
# the prod manifest the helpers fall back to unhashed paths. Dev compose
# bind-mounts the repo, so run `npm run build` once after cloning.
RUN test -f /var/www/html/assets/build/.vite/manifest.json || (echo "Missing Vite prod manifest: run 'npm run build' before 'docker build' (see DOCKER-README.md)." >&2; exit 1)
RUN mkdir -p /var/www/html/public/uploads /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 CMD curl -f http://localhost/ || exit 1
