<?php
namespace common\filter;

class Input
{

	private $escaper;

	function __construct()
	{
		$this->escaper = new \Phalcon\Escaper();
	}

	/**
	 * 转义XSS字符 支持字符串和一维数组
	 * 
	 * @param mixd $str
	 * @return string
	 */
	public function filterXss($str)
	{
		if(is_array($str))
		{
			foreach($str as $k => $v)
			{
				$str[$k] = $this->escaper->escapeHtml(trim($v));
			}
		}
		else
		{
			return $this->escaper->escapeHtml(trim($str));
		}
		return $str;
	}

	/**
	 * 处理成安全的URL
	 * 
	 * @param string $str
	 * @return string
	 */
	public function filterUrl($str)
	{
		return $this->escaper->escapeUrl($str);
	}
}

?>