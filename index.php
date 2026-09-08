<?php

use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\Settings;
use App\Core\System;

const APP_ROOT = __DIR__;

require_once __DIR__ . '/vendor/autoload.php';

// Timezone is configurable via Config `timezone` or Env `APP_TIMEZONE` with
// a sane UTC default (see App\Core\Settings::defaultTimezone()). Env is
// loaded before the early read so a .env-only APP_TIMEZONE is honored here
// instead of falling back to UTC; System re-applies the timezone after
// config boots so Config `timezone` still wins.
Env::load(APP_ROOT);

try {
    Settings::applyDefaultTimezone();
} catch (\Throwable) {
    date_default_timezone_set(Settings::TIMEZONE_DEFAULT);
}

// Session startup is owned by System::bootstrap via Session::start() so
// cookie flags come from config; nothing starts a session here.

try {
    $app = new System();
    $app->bootstrap()
        ->processRequest()
        ->complete();
} catch (\Throwable $e) {
    ErrorHandler::handle($app ?? null, $e);
}
