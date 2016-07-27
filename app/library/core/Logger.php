<?php
namespace core;
use Phalcon\Logger\Adapter\File as FileAdapter;

class Logger
{


	public static function write($fileName, $data, $path=false)
	{
		$logPath = \core\Config::item('develop')->logPath;
		$folder=$path == false?$logPath .date("Y-m-d"). '/':$logPath.$path.'/'.date("Y-m-d"). '/';
		self::checkFolderExists($folder);
		$logger = new FileAdapter($folder.$fileName.'.log');
		if(!chmod($folder.$fileName.'.log', 0777))
		{
			error_log(date('Y-m-d H:i:s') . $folder.$fileName.'.log', 3, '/tmp/xf_'. date('Y-m-d') . '.log');
		}
		$data=is_array($data) ?json_encode($data) :$data;
		$logger->log($data);
		$logger->close();
		unset($fileName);
		unset($data);
	}
	/**
	 * 判断文件夹是否存在，如果不存在则创建
	 * @param string $folder
	 */
	private static function checkFolderExists($folder)
	{
		if(!is_dir($folder))
		{
			mkdir($folder , 0777 , true);
		}
	}
}