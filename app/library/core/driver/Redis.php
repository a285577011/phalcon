<?php
namespace core\driver;

class Redis
{

	public static function getInstance($configName = 'default')
	{
		$redis = new \Redis();
		$redisConfig = \core\Config::item('redis');
		$redisConfig = $redisConfig->$configName;
		$timeOut = isset($redisConfig->timeout)? intval($redisConfig->timeout): 3;
		if($redis->connect($redisConfig->ip, $redisConfig->port, $timeOut))
		{
			$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
			return $redis;
		}
		else
		{
			$msg = DEBUG? 'redis is down':'系统繁忙！';
			throw new \Exception($msg);
		}
	}
}