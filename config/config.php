<?php
return [
    /**
     * Base URL for app
     */
    'baseUrl' => 'http://local.roolith-framework.me/',

    /**
     * Database configuration
     */
    'database' => [
        'host' => 'localhost',
        'name' => 'roolith_cms',
        'user' => 'root',
        'pass' => '',
    ],
//    'database' => null,

    /**
     * For domain to have www or not www in domain
     */
    'forceNonWww' => true,

    /**
     * Current app version
     */
    'version' => time(),
];
