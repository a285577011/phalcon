<?php
namespace core;

class EnameApi
{

	private $appConfig;

	private $clientIp;

	private $error;

	function __construct($config = null)
	{
		$config or $config = \core\Config::item('apiTrans');
		$this->appConfig = $config->develop;
	}

	public function sendCmd($action, array $params, $post = true)
	{
		$url = $this->appConfig->url . '/' . $action;
		$default = array('user'=> $this->appConfig->user,'appkey'=> $this->appConfig->appkey);
		$curl = new \core\AppCurl($url);
		$result = true;
		if($post)
		{
			$params = array_merge($default, $params);
			$result = $curl->post($params);
		}
		else
		{
			$result = $curl->get($params, $default);
		}
		if(false == $result)
		{
			$this->error = $curl->getError();
			return false;
		}
		return $result;
	}

	public function getError()
	{
		return $this->error;
	}
}