<?php
header('Content-Type: text/html; charset=utf-8');
use Phalcon\Mvc\View;
use Phalcon\Mvc\Dispatcher;
// 根目录定义
define("ROOT_PATH", dirname(__DIR__) . '/');
// 开发模式 TRUE | 线上模式 FALSE
define('DEBUG', TRUE);
// Read the configuration
$config = new Phalcon\Config\Adapter\Ini('../app/config/config.ini');
try
{
	// Register an autoloader
	$loader = new \Phalcon\Loader();
	$loader->registerDirs(
		array($config->application->controllersDir,$config->application->pluginsDir,$config->application->libraryDir,
				$config->application->modelsDir, $config->application->vendorDir))
		->register();
	// Create a DI
	$di = new \Phalcon\DI\FactoryDefault();
	// Setup the view component
	$di->set('view', 
		function () use($config)
		{
			$view = new \Phalcon\Mvc\View();
			$view->setViewsDir($config->application->viewsDir);
			$view->disableLevel(View::LEVEL_MAIN_LAYOUT);
			return $view;
		});
	
	// Setup a base URI so that all generated URIs include the "tutorial" folder
	$di->set('url', 
		function () use($config)
		{
			$url = new \Phalcon\Mvc\Url();
			$url->setBaseUri($config->application->baseUri);
			return $url;
		});
	$di->set('dispatcher', 
		function () use($di)
		{
			$eventsManager = $di->getShared('eventsManager');
			$eventsManager->attach('dispatch:beforeException', 
				function ($event, $dispatcher, $exception)
				{
					switch($exception->getCode())
					{
						case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
						case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
							header("HTTP/1.0 404 Not Found");
							header("status: 404 Not Found");
							exit();
							break; // for checkstyle
						default:
							header('HTTP/1.1 500 Internal Server Error');
							return false;
							exit();
							break; // for checkstyle
					}
				});
			$dispatcher = new Dispatcher();
			$dispatcher->setEventsManager($eventsManager);
			return $dispatcher;
		}, true);
	/**
	 * Add routing capabilities
	 */
	$di->set(
		'router',
		function () {
			require '../app/config/routes.php';
	      return $router;
		}
	);
	// Handle the request
	$application = new \Phalcon\Mvc\Application($di);
	if(DEBUG)
	{
		register_shutdown_function(array('\core\Handler','myExceptionHandler'));
	}
	echo $application->handle()
		->getContent();
}
catch(\Exception $e)
{
	//echo $e->getMessage();
	file_put_contents('/var/www/exception.log', date("Y-m-d H:i:s") . '-' . $e->getMessage() . "\n");
	exit();
}
?>

