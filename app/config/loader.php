<?php

use Phalcon\Loader;

$loader = new Loader();

/**
 * Register Namespaces
 */
$loader->registerNamespaces([
    'Huubook\Models' => APP_PATH . '/common/models/',
    'Huubook' => APP_PATH . '/common/library/',
]);

/**
 * Register module classes
 */
$loader->registerClasses([
    'Huubook\Modules\Frontend\Module' => APP_PATH . '/modules/frontend/Module.php',
    'Huubook\Modules\Cli\Module' => APP_PATH . '/modules/cli/Module.php',
    'Huubook\Modules\Backend\Module' => APP_PATH . '/modules/backend/Module.php',
    'Huubook\Modules\Api\Module' => APP_PATH . '/modules/api/Module.php',
]);

$loader->register();
