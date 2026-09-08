<?php

const APP_ROOT = __DIR__;

require_once __DIR__ . '/vendor/autoload.php';

// Full lifecycle (env, bootstrap, request, teardown, error handling) lives
// in App\Core\System::run(); the front controller stays a thin bootstrap.
App\Core\System::run();
