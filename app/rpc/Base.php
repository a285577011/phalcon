<?php

class Base
{

	/**
	 *
	 * @var Phalcon\DI\FactoryDefault
	 */
	protected $di;

	/**
	 * 创建对象
	 * 
	 * @param object 容器对象
	 */
	public function __construct($di)
	{
		// 配置初始化
	$this->di = $di;
	 \core\Config::init(DEBUG);
	}

	/**
	 * 最终输出格式化
	 * 
	 * @param array | object | string 要输出的信息
	 * @param int 数字码
	 * @return object
	 */
	protected function output($data, $code = 40000)
	{
		// 输出对象
		$output = new \stdClass();
		// 是否是正常的返回流程
		$output->flag = ($code < 49000);
		// 代码号
		$output->code = $code;
		// 输出信息
		$output->msg = $data;
		// 最终输出
		return $output;
	}
}