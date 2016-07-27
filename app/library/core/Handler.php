<?php
namespace core;

class Handler
{

	/**
	 * 在PHP程序终止的时候记录错误的原因
	 */
	public static function myExceptionHandler()
	{
		$errInfo = error_get_last();
		if($errInfo && is_array($errInfo))
		{
			error_log(date('Y-m-d H:i:s') . var_export($errInfo, TRUE), 3, 
				'/tmp/phalcon_error' . date('Y-m-d') . ".log");
		}
	}
}