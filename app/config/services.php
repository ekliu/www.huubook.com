<?php

use Phalcon\Cache\Backend\Redis as BackRedis;
use Phalcon\Cache\Frontend\Data as FrontData;
use Phalcon\Db\Profiler as ProfilerDb;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Mvc\Model\Metadata\Files as MetadataFiles;
use Phalcon\Mvc\Model\Metadata\Redis as MetadataRedis;
use Phalcon\Queue\Beanstalk;


/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {
    $config = $this->getConfig();

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $params = [
        'host'     => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname'   => $config->database->dbname,
        'charset'  => $config->database->charset
    ];

    if ($config->database->adapter == 'Postgresql') {
        unset($params['charset']);
    }

    $connection = new $class($params);

    return $connection;
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
//$di->setShared('modelsMetadata', function () {
//    return new MetaDataAdapter();
//});

/**
 * Configure the Volt service for rendering .volt templates
 */
//$di->setShared('voltShared', function ($view) {
//    $config = $this->getConfig();
//    $volt = new VoltEngine($view, $this);
//    $volt->setOptions([
//        'compiledPath' => function($templatePath) use ($config) {
//
//            // Makes the view path into a portable fragment
//            $templateFrag = str_replace($config->application->appDir, '', $templatePath);
//
//            // Replace '/' with a safe '%%'
//            $templateFrag = str_replace('/', '%%', $templateFrag);
//
//            return $config->application->cacheDir . 'volt/' . $templateFrag . '.php';
//        }
//    ]);
//
//    return $volt;
//});

///////////////////////////////////////////////////////////////////数据库调试模式///////////////////////////////////////////////////////////////////
/**
 * FirePHP服务
 */
$di->setShared('firephp', function () {
    return \Huubook\FirePHP::getInstance(true);
});


/**
 * 数据库跟踪
 */
$di->set(
    "profiler",
    function () {
        return new ProfilerDb();
    },
    true
);


/**
 * 主数据库配置（读写）
 */
$di->setShared('dbm', function () use ($di) {
    //建立主数据库连接
    $config = $this->getConfig();
    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->db_master->adapter;
    $params = [
        'host' => $config->db_master->host,
        'username' => $config->db_master->username,
        'password' => $config->db_master->password,
        'dbname' => $config->db_master->dbname,
        'charset' => $config->db_master->charset
    ];
    $connection = new $class($params);
    $eventsManager = new EventsManager();
    // Get a shared instance of the DbProfiler
    $profiler = $di->getProfiler();
    // Listen all the database events
    $eventsManager->attach(
        'db',
        function ($event, $connection) use ($profiler, $di) {

            if ($event->getType() === "beforeQuery") {
                $profiler->startProfile($connection->getSQLStatement());
            }

            if ($event->getType() === "afterQuery") {

                $profiler->stopProfile();

                if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'mydev') !== false) {

                    $fp = $di->get('firephp');
                    $sql = $connection->getSQLStatement();
                    $vars = $connection->getSQLVariables();

                    //过滤 没用sql
                    if (strpos($sql, 'DESCRIBE') === 0 || strpos($sql, 'INFORMATION_SCHEMA') !== false) {
                        return true;
                    }

                    if ($vars) {
                        $keys = array();
                        $values = array();
                        foreach ($vars as $placeHolder => $var) {
                            // fill array of placeholders
                            if (is_string($placeHolder)) {
                                $keys[] = '/:' . ltrim($placeHolder, ':') . '/';
                            } else {
                                $keys[] = '/[?]/';
                            }
                            // fill array of values
                            // It makes sense to use RawValue only in INSERT and UPDATE queries and only as values
                            // in all other cases it will be inserted as a quoted string
                            if ((strpos($sql, 'INSERT') === 0 || strpos($sql, 'UPDATE') === 0) && $var instanceof \Phalcon\Db\RawValue) {
                                $var = $var->getValue();
                            } elseif (is_null($var)) {
                                $var = 'NULL';
                            } elseif (is_numeric($var)) {
                                $var = $var;
                            } else {
                                $var = '"' . $var . '"';
                            }
                            $values[] = $var;
                        }
                        $sql = preg_replace($keys, $values, $sql, 1);
                    }


                    $profile = $profiler->getLastProfile();
                    $run_time = number_format($profile->getTotalElapsedSeconds(), 4);
                    $sql = $sql . '  [' . $run_time . 's]';
                    $fp->fb($sql, 'dbm-SQL:');

                }
            }
        }
    );

    //Assign the eventsManager to the db adapter instance
    $connection->setEventsManager($eventsManager);
    return $connection;
});


/**
 * 从数据库配置（只读）
 */
$di->set('dbs', function () use ($di) {
    $config = $this->getConfig();
    $class = 'Phalcon\Db\Adapter\Pdo\\' . $config->db_slave->adapter;
    $params = [
        'host' => $config->db_slave->host,
        'username' => $config->db_slave->username,
        'password' => $config->db_slave->password,
        'dbname' => $config->db_slave->dbname,
        'charset' => $config->db_slave->charset
    ];
    $connection = new $class($params);
    $eventsManager = new EventsManager();
    // Get a shared instance of the DbProfiler
    $profiler = $di->getProfiler();
    // Listen all the database events
    $eventsManager->attach(
        'db',
        function ($event, $connection) use ($profiler, $di) {

            if ($event->getType() === "beforeQuery") {
                $profiler->startProfile($connection->getSQLStatement());
            }

            if ($event->getType() === "afterQuery") {

                $profiler->stopProfile();

                if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'mydev') !== false) {

                    $fp = $di->get('firephp');
                    $sql = $connection->getSQLStatement();
                    $vars = $connection->getSQLVariables();

                    //过滤 没用sql
                    if (strpos($sql, 'DESCRIBE') === 0 || strpos($sql, 'INFORMATION_SCHEMA') !== false) {
                        return true;
                    }

                    if ($vars) {
                        $keys = array();
                        $values = array();
                        foreach ($vars as $placeHolder => $var) {
                            // fill array of placeholders
                            if (is_string($placeHolder)) {
                                $keys[] = '/:' . ltrim($placeHolder, ':') . '/';
                            } else {
                                $keys[] = '/[?]/';
                            }
                            // fill array of values
                            // It makes sense to use RawValue only in INSERT and UPDATE queries and only as values
                            // in all other cases it will be inserted as a quoted string
                            if ((strpos($sql, 'INSERT') === 0 || strpos($sql, 'UPDATE') === 0) && $var instanceof \Phalcon\Db\RawValue) {
                                $var = $var->getValue();
                            } elseif (is_null($var)) {
                                $var = 'NULL';
                            } elseif (is_numeric($var)) {
                                $var = $var;
                            } else {
                                $var = '"' . $var . '"';
                            }
                            $values[] = $var;
                        }
                        $sql = preg_replace($keys, $values, $sql, 1);
                    }


                    $profile = $profiler->getLastProfile();
                    $run_time = number_format($profile->getTotalElapsedSeconds(), 4);
                    $sql = $sql . '  [' . $run_time . 's]';
                    $fp->fb($sql, 'dbs-SQL:');

                }
            }
        }
    );
    //Assign the eventsManager to the db adapter instance
    $connection->setEventsManager($eventsManager);
    return $connection;
});

/**
 * 队列服务
 */
$di->setShared('queue', function () {
    $config = $this->getConfig();
    return new Beanstalk(
        [
            'host' => $config->beanstalkd->host,
            'port' => $config->beanstalkd->port,
        ]
    );
});


/**
 * 缓存服务
 */
$di->setShared('cache', function () {
    $config = $this->getConfig();
    $frontCache = new FrontData(
        [
            "lifetime" => $config->cache->lifetime,
        ]
    );

    return new BackRedis(
        $frontCache,
        [
            "host" => $config->redis->host,
            "port" => $config->redis->port,
            "persistent" => $config->redis->persistent,
            "index" => $config->redis->index,
            'prefix' => $config->redis->prefix
        ]
    );

});
