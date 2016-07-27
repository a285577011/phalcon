<?php
namespace common;

class Client
{

	/**
	 * 获取客户端的IP 当CDN=1的时候获取CDN来的IP
	 * CDN=2的时候获取HA传来的IP
	 * 其他时间不获取任何代理IP
	 * 
	 * @param object $config
	 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
	 * @return mixed
	 */
	public static function getClientIp($type = 1)
	{
		$type = $type? 1: 0;
		static $ip = NULL; // 静态变量存储IP
		if($ip !== NULL)
			return $ip[$type];
			if(getenv('HTTP_CLIENT_IP'))
			{
				$ip = getenv('HTTP_CLIENT_IP');
			}
			elseif(getenv('HTTP_X_FORWARDED_FOR'))
			{
				// 获取客户端用代理服务器访问时的真实ip 地址
				if(strpos(getenv('HTTP_X_FORWARDED_FOR'), ',') !== false)
				{
					$ips = explode(',', getenv('HTTP_X_FORWARDED_FOR'));
					$ip = trim($ips[count($ips) - 1]);
				}
				else
				{
					$ip = getenv('HTTP_X_FORWARDED_FOR');
				}
			}
			elseif(getenv('HTTP_X_FORWARDED'))
			{
				$ip = getenv('HTTP_X_FORWARDED');
			}
			elseif(getenv('HTTP_FORWARDED_FOR'))
			{
				$ip = getenv('HTTP_FORWARDED_FOR');
			}
			elseif(getenv('HTTP_FORWARDED'))
			{
				$ip = getenv('HTTP_FORWARDED');
			}
		if(empty($ip))
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		// IP地址合法验证
		$long = sprintf("%u", ip2long($ip));
		$ip = $long? array($ip,$long): array('0.0.0.0',0);
		return $ip[$type];
	}
}