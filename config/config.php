<?php
return [
    /**
     * Base URL for app
     */
    "baseUrl" => "http://localhost:8080/",

    /**
     * Vite dev server url used in development to serve assets with HMR
     *
     * Set an empty string to always use built assets from the assets folder
     */
    "viteDevServer" => "", // if vite server is running add http://localhost:5173

    /**
     * Database configuration
     */
//    "database" => [
//        "host" => "localhost",
//        "name" => "roolith_cms",
//        "user" => "root",
//        "pass" => "",
//    ],
        'database' => null,

    /**
     * For domain to have www or not www in domain
     */
    "forceNonWww" => true,

    /**
     * Current app version
     */
    "version" => time(),
];
