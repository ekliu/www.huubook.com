<?php
/*
 * Modified: prepend directory path of current file, because of this file own different ENV under between Apache and command line.
 * NOTE: please remove this comment.
 */
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');

return new \Phalcon\Config([
    'version' => '1.0',

//    'database' => [
//        'adapter'  => 'Mysql',
//        'host'     => 'localhost',
//        'username' => 'root',
//        'password' => '',
//        'dbname'   => 'huubook',
//        'charset'  => 'utf8',
//    ],

    'database' => [
        'adapter'     => 'Mysql',
        'host'        => 'dbm1.rs.youfa365.com',
        'username'    => 'dbm1',
        'password'    => 'MnOYBBXNsuxmUcSHlt2JNOecjKa30F',
        'dbname'      => 'huubook',
        'charset'     => 'utf8',
    ],

    'db_master' => [
        'adapter'     => 'Mysql',
        'host'        => 'dbm1.rs.youfa365.com',
        'port'        => '3306',
        'username'    => 'dbm1',
        'password'    => 'MnOYBBXNsuxmUcSHlt2JNOecjKa30F',
        'dbname'      => 'huubook',
        'charset'     => 'utf8',
    ],
    'db_slave' => [
        'adapter'     => 'Mysql',
        'host'        => 'dbs1.rs.youfa365.com',
        'port'        => '3306',
        'username'    => 'dbm1',
        'password'    => 'MnOYBBXNsuxmUcSHlt2JNOecjKa30F',
        'dbname'      => 'huubook',
        'charset'     => 'utf8',
    ],

    //缓存
    'redis' => [
        'host' => 'rs1.rs.youfa365.com',
        'port' => '6379',
//        'auth' => 'dirdir',
        'persistent' => false,
        'index' => 0,
        'prefix' => '',
    ],

    //队列
    'beanstalkd' => [
        'host' => 'qeue.rs.youfa365.com',
        'port' => '11300',
    ],
    'cache' => [
        'lifetime' => 172800,
    ],
    'metadata' => [
        'statsKey' => '_PHCM_MM',
        "lifetime"   => 172800,
        "index"      => 2,
    ],

    'application' => [
        'appDir'         => APP_PATH . '/',
        'modelsDir'      => APP_PATH . '/common/models/',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'cacheDir'       => BASE_PATH . '/cache/',

        // This allows the baseUri to be understand project paths that are not in the root directory
        // of the webpspace.  This will break if the public/index.php entry point is moved or
        // possibly if the web server rewrite rules are changed. This can also be set to a static path.
        'baseUri'        => preg_replace('/public([\/\\\\])index.php$/', '', $_SERVER["PHP_SELF"]),
    ],

    /**
     * if true, then we print a new line at the end of each CLI execution
     *
     * If we dont print a new line,
     * then the next command prompt will be placed directly on the left of the output
     * and it is less readable.
     *
     * You can disable this behaviour if the output of your application needs to don't have a new line at end
     */
    'printNewLine' => true
]);
