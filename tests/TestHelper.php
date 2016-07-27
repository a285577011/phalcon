<?php
use Phalcon\DI, Phalcon\DI\FactoryDefault;

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('TEST_ROOT_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));
define('PATH_LIBRARY', __DIR__ . '/../app/library/');
define('PATH_SERVICES', __DIR__ . '/../app/services/');
define('PATH_RESOURCES', __DIR__ . '/../app/resources/');
define('MODELS_PATH', __DIR__ . '/../app/models/');
define('DEBUG', TRUE);
set_include_path(TEST_ROOT_PATH . PATH_SEPARATOR . get_include_path());

// required for phalcon/incubator
// include __DIR__ . "/vendor/autoload.php";

// use the application autoloader to autoload the classes
// autoload the dependencies found in composer
$loader = new \Phalcon\Loader();

$loader->registerDirs(array(TEST_ROOT_PATH,PATH_LIBRARY,PATH_SERVICES,PATH_RESOURCES,MODELS_PATH));
$loader->registerNamespaces(array('Phalcon'=> TEST_ROOT_PATH . '/incubator-1.2.4/Library/Phalcon'));
$loader->register();

$di = new FactoryDefault();
define('CONTROLLER_NAME','');
define('ACTION_NAME','');
DI::reset();
// 配置初始化
\core\Config::init(DEBUG);
\core\Config::setConfig(ROOT_PATH.'/app/config/debug.ini');
// add any needed services to the DI here

DI::setDefault($di);