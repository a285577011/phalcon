<?php
namespace core;

class AppCurl
{

	/**
	 * 请求的URL
	 *
	 * @var string
	 */
	private $url;

	/**
	 * 超时时间 单位秒
	 *
	 * @var int
	 */
	private $timeOut = 60;

	/**
	 * 请求方法
	 *
	 * @var int
	 */
	private $method; // 1 = get ,2 = post
	
	/**
	 * 错误标识
	 *
	 * @var boolean
	 */
	private $error = false;

	/**
	 * 错误消息
	 *
	 * @var string
	 */
	private $errMsg;

	function __construct($url, $timeOut = 60)
	{
		$this->url = $url;
		$this->timeOut = $timeOut;
	}

	/**
	 * 执行GET请求
	 * 
	 * @param array $data
	 * @return boolean || string
	 */
	public function get($data,$default)
	{
		$this->method = 1;
		$this->url = $this->url . '?' . http_build_query($data);
		return $this->createCurl($default);
	}

	/**
	 * 执行POST请求
	 * 
	 * @param array $data
	 * @return boolean || string
	 */
	public function post($data)
	{
		$this->method = 2;
		return $this->createCurl($data);
	}

	/**
	 * 设置错误信息
	 * 
	 * @return string
	 */
	public function getError()
	{
		return $this->errMsg;
	}

	/**
	 * 创建CURL函数
	 * 
	 * @param array $data
	 * @return boolean mixed
	 */
	private function createCurl($data = array())
	{
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "eName php team");
		$output = curl_exec($ch);
		if(curl_errno($ch))
		{
			$this->error = true;
			$this->errMsg = curl_error($ch);
		}
		curl_close($ch);
		if($this->error)
		{
			return false;
		}
		return $output;
	}
}