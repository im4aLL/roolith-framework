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
if (!defined('ROOLITH_CONFIG_ROOT')) {
    define('ROOLITH_CONFIG_ROOT', APP_ROOT . '/config');
}

/**
 * Where the views are stored.
 */
if (!defined('APP_VIEW_ROOT')) {
    define('APP_VIEW_ROOT', APP_ROOT . '/views');
}

/**
 * Turn on or off CMS feature (037-C4).
 *
 * Explicit env flag: set APP_ENABLE_CMS=1 in .env to mount CMS routes plus
 * admin helpers. Default stays off so core boots without the optional CMS
 * release asset (see docs/cms-installer.md). The optional cms-constant.php
 * file remains a legacy override when present; System.php loads it after
 * this flag so deploys with existing files keep working.
 *
 * If you turn it off, all files under admin folder (Admin/*) will be deactivated.
 */
if (!defined('APP_ENABLE_CMS')) {
    $cmsFlag = \App\Core\Env::get('APP_ENABLE_CMS', '0');

    define('APP_ENABLE_CMS', filter_var($cmsFlag, FILTER_VALIDATE_BOOLEAN));
}
