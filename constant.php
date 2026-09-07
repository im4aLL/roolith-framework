<?php

/**
 * Environment
 *
 * BC bridge: single source is APP_ENV (see App\Core\Env) with a fail-closed
 * production default. ROOLITH_ENV mirrors it for legacy checks.
 *
 * Safe here: constant.php is required from System::__construct after
 * Env::load, and the composer autoloader is already registered (index.php,
 * phpunit bootstrap, and CLI all require vendor/autoload.php first), so the
 * Env class is available.
 */
if (!defined('ROOLITH_ENV')) {
    define('ROOLITH_ENV', \App\Core\Env::appEnv());
}

/**
 * Where the configuration files are stored.
 */
const ROOLITH_CONFIG_ROOT = APP_ROOT . '/config';

/**
 * Where the views are stored.
 */
const APP_VIEW_ROOT = APP_ROOT . '/views';

/**
 * Turn on or off CMS feature
 *
 * If you turn it off, all files under admin folder (Admin/*) will be deactivated
 */
const APP_ENABLE_CMS = false;
