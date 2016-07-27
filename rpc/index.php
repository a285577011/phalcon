<?php
define("ROOT_PATH", dirname(__DIR__) . '/');
// 开发模式 TRUE | 线上模式 FALSE
define('DEBUG', true);
// Read the configuration
$config = new Phalcon\Config\Adapter\Ini(ROOT_PATH . '/app/config/rpc.ini');
try
{
	
	// Register an autoloader
	$loader = new \Phalcon\Loader();
	$loader->registerDirs(
		array($config->application->rcpContrDir,$config->application->libraryDir,$config->application->modelsDir))
		->register();
	
	// Create a DI
	$di = new Phalcon\DI\FactoryDefault();
	
	$class = 'index';
	$url = isset($_GET['_url'])? trim($_GET['_url']): false;
	if(false != $url)
	{
		$urlArr = explode('/', $url);
		$class = $urlArr[1];
	}
	$className = ucfirst($class) . 'Rpc';
	if(class_exists($className))
	{
		$service = new Yar_Server(new $className($di));
		$service->handle();
	}
	else
	{
		throw new Exception('the rpc controllers not exits');
	}
}
catch(\Phalcon\Exception $e)
{
	echo "system error: ", $e->getMessage();
}
catch(\Exception $e)
{
	echo $e->getMessage();
}