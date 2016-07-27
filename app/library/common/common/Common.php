<?php
namespace common\common;
class Common
{
	/**
	 * 根据交易ID得到一个可以解密的字符 在前台页面使用淘域名ID的时候使用
	 *
	 * @param int $transId
	 * @return string
	 */
	public static function getSortUrl($transId)
	{
		$str = base_convert($transId * 10000 + 268, 10, 36);
		$arr = str_split($str);
		$rand = '0123456789abcdefghijklmnopqrstuvwxyz';
		$newStr = array();
		foreach($arr as $v)
		{
			$newStr[] = $v;
			$newStr[] = $rand[mt_rand(0, 25)];
		}
		return implode('', $newStr);
	}
}