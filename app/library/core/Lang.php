<?php
namespace core;

class Lang
{

	/**
	 * 获取浏览器支持的语言
	 */
	private static $supportLang = false;

	/**
	 * 默认支持的语言
	 */
	private static $default = 'zh_CN';

	/**
	 * 当前语言变量缓存
	 */
	private static $lang = false;

	/**
	 * 语言对象
	 */
	private static $translate = false;

	/**
	 * 获取浏览器支持的第一个语言选项
	 */
	private static function getSupportLang()
	{
		$lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])? $_SERVER['HTTP_ACCEPT_LANGUAGE']: false;
		if(false !== $lang)
		{
			$langArr = explode(',', $lang);
			if(count($langArr) > 0)
			{
				self::$supportLang = $langArr[0];
			}
		}
		if(false == self::$supportLang)
		{
			self::$supportLang = self::$default;
		}
	}

	/**
	 * 根据浏览器获取到的支持语言名字转换成文件夹名字
	 * 经过测试 文件夹的名字如果有-会读取不到
	 *
	 * @param string $name
	 * @return string
	 */
	public static function getLangName()
	{
		$language = array('zh-CN'=> 'zh_CN');
		if(false == self::$supportLang)
		{
			self::getSupportLang();
		}
		return isset($language[self::$supportLang])? $language[self::$supportLang]: self::$default;
	}

	/**
	 * 提供全局函数 主要是方便在VIEW里面使用多语言的翻译函数 其他函数不要写在这个里面 走namespace和方式
	 * 如果有变量请在$value里面传值 array('name'=>'value')
	 *
	 * @param string $key
	 * @param array $value
	 */
	public static function e($key, array $value = array())
	{
		if(false == self::$lang)
		{
			self::$lang = self::getLangName();
		}
		if(false == self::$translate)
		{
			self::$translate = new \Phalcon\Translate\Adapter\Gettext(
				array('locale'=> self::$lang,'defaultDomain'=> 'common','directory'=> ROOT_PATH . '/app/lang'));
		}
		return self::$translate->_($key, $value);
	}
}