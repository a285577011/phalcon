<?php
namespace core;

class Config
{

	/**
	 * 配置选项
	 * 
	 * @var object
	 */
	private static $config;

	/**
	 * 创建配置对象,读取配置
	 * 
	 * @param boolean 调试状态
	 */
	public static function init($debug = FALSE)
	{
		self::$config = $debugs = new \StdClass();
		
		// 读取目录下所有文件
		$files = glob(ROOT_PATH . '/app/config/autoload/*.*');
		
		// 是调试模式
		if($debug)
		{
			$files[] = ROOT_PATH . '/app/config/debug.ini';
			error_reporting(E_ALL);			
		}
		else
		{
			$files[] = ROOT_PATH . '/app/config/global.ini';
			error_reporting(0);				
		}
		
		// 文件读取
		foreach($files as $file)
		{
			$class = "\\Phalcon\Config\Adapter\\" . ucfirst(substr($file, - 3));
			
			// 读取配置信息
			$config = new $class($file);
			
			// 整合数据
			foreach($config as $key => $val)
			{
				self::$config->$key = $val;
			}
		}
	}

	/**
	 * 获取配置数组或者配置项
	 * 
	 * @param string 对象属性
	 * @param string 子对象属性
	 * @return object string boolean
	 */
	public static function item($index, $item = NULL)
	{
		// 此数组是否存在
		$pref = isset(self::$config->$index)? self::$config->$index: new \StdClass();
		
		// 获取的下标值是否存在
		if($item && isset($pref->$item))
		{
			$pref = $pref->$item;
		}
		
		return $pref? : FALSE;
	}

	/**
	 * 为了方便单元测试使用另一个DB 提供一个覆盖APP里面DB配置文件的方法 只支持ini文件
	 * 
	 * @param string $file
	 */
	public static function setConfig($file)
	{
		$config = new \Phalcon\Config\Adapter\Ini($file);
		foreach($config as $key => $val)
		{
			self::$config->$key = $val;
		}
	}
}