<?php
namespace common\domain;
use core\Config;

class Domain
{

	/**
	 * 获取域名分组信息
	 *
	 * @param string $domainName
	 * @return array
	 */
	public static function getDomainGroup($domainName)
	{
		$groupId = self::getDomainSystemGroupOne($domainName);
		return array('domainClass'=> self::getDomainClassName($domainName),'domainSysOne'=> $groupId,
				'domainSysTwo'=> self::getDomainSystemGroupTwo($domainName),
				'domainLength'=> mb_strlen(self::getDomainBody($domainName), "UTF-8"));
	}

	/**
	 * 获取系统分组1
	 *
	 * @param string $domain
	 * @return int
	 */
	public static function getDomainSystemGroupOne($domain)
	{
		$domain = self::getDomainBody($domain);
		$groupId = self::getDomainSystemGroupOneSub($domain);
		return $groupId;
	}

	/**
	 * 获取域名主体部分
	 *
	 * @param string $domain
	 */
	public static function getDomainBody($domain)
	{
		$domainArr = explode('.', $domain);
		return strtolower($domainArr[0]);
	}

	/**
	 * 获取域名的系统分组
	 *
	 * @param string $domain
	 */
	private static function getDomainSystemGroupOneSub($domain)
	{
		$groupId = 0;
		if(preg_match("/([\x{4e00}-\x{9fa5}])+/ui", $domain))
		{
			return $groupId; // 0 中文
		}
		if(preg_match('/^\d+$/', $domain))
		{
			$groupId = 1; // 纯数字
		}
		elseif(preg_match('/^[a-zA-Z]+$/', $domain))
		{
			$groupId = 101; // 纯字母
		}
		elseif(strlen($domain) == 2)
		{
			$groupId = 202; // 两杂
		}
		elseif(strlen($domain) == 3 && FALSE === strripos($domain, '-'))
		{
			$groupId = 203; // 三杂
		}
		else
		{
			$groupId = 201; // 杂米
		}
		return $groupId;
	}

	/**
	 * 获取域名类型
	 *
	 * @param string $domainName
	 * @return number
	 */
	public static function getDomainClassName($domainName)
	{
		if(self::isInterDomain($domainName))
		{
			return self::isCnDomain($domainName)? 4: 2;
		}
		elseif(self::isChinaDomain($domainName))
		{
			return self::isCnDomain($domainName)? 3: 1;
		}
		else
		{
			return 2;
		}
	}

	/**
	 * 获取系统分组2
	 *
	 * @param string $domain
	 * @return number
	 */
	public static function getDomainSystemGroupTwo($domain)
	{
		$domain = strtolower(self::getDomainBody($domain));
		if(self::isCnDomain($domain) || strlen($domain) > 18) // 有中文或是超过18个字符，直接返回0
		{
			return 0;
		}
		$pinyin = self::pinyinSplit($domain);
		if(! empty($pinyin) && count($pinyin) < 4)
		{
			return count($pinyin);
		}
		else
		{
			return 0;
		}
	}

	/**
	 * 是否是国际域名 包含.asia域名
	 *
	 * @param string $domain
	 * @author zougc
	 * @return boolean
	 */
	public static function isInterDomain($domain)
	{
		$ok = false;
		$ext = strtolower(substr($domain, strlen($domain) - 3));
		if($ext == 'com' || $ext == 'net' || $ext == 'org' || $ext == 'edu' || $ext == 'sia')
		{
			$ok = true;
		}
		return $ok;
	}

	/**
	 * 判断是否是中文域名 判断是否有汉字
	 *
	 * @param string $domain
	 * @return boolean
	 * @author zougc
	 */
	public static function isCnDomain($domain)
	{
		if(preg_match("/[\x{4e00}-\x{9fa5}]+\S+$/u", $domain))
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 判断是否是CNNIC管辖的域名
	 *
	 * @param sting $domain
	 * @author zougc
	 * @return boolean
	 */
	public static function isChinaDomain($domain)
	{
		$ok = FALSE;
		if(strtolower(substr($domain, strlen($domain) - 2)) == 'cn')
		{
			$ok = true;
		}
		elseif(stripos($domain, '.中国') !== FALSE || stripos($domain, '.公司') !== FALSE ||
			 stripos($domain, '.网络') !== FALSE)
		{
			$ok = true;
		}
		return $ok;
	}

	/**
	 * 分割拼音
	 *
	 * @param string $pinyin
	 * @return mixed:
	 */
	public static function pinyinSplit($domainBody)
	{
		$domainPinyin = Config::item('domainPinyin');
		$pyDic = explode('|', $domainPinyin);
		$len = strlen($domainBody);
		$p = array_fill(0, $len + 1, $len);
		$p[$len] = 0;
		$s = array_fill(0, $len, 0);
		
		for($i = $len - 1; $i >= 0; $i--)
		{
			for($j = 0, $max = $len - $i; $j < $max; $j++)
			{
				if($j == 0 or
					 array_search(substr($domainBody, $i, $j + 1), $pyDic) !== FALSE and $p[$i + $j + 1] + 1 < $p[$i])
				{
					$p[$i] = $p[$i + $j + 1] + 1;
					$s[$i] = $j + 1;
				}
			}
		}
		$tmp = 0;
		$result = array();
		while($tmp < $len)
		{
			$py = substr($domainBody, $tmp, $s[$tmp]);
			$tmp += $s[$tmp];
			if(array_search($py, $pyDic) !== FALSE)
			{
				array_push($result, $py);
			}
			else
			{
				return FALSE;
			}
		}
		return $result;
	}

	/**
	 * 获取域名后缀类型
	 *
	 * @param string $domain
	 * @return string
	 */
	public static function getDomainClassAll($domain)
	{
		if($domain)
		{
			$domainArr = explode('.', $domain);
			return count($domainArr) == 3? ($domainArr[1] . '.' . $domainArr[2]): $domainArr[1];
		}
	}

	/**
	 *
	 * @param unknown $config
	 * @param unknown $domain
	 * @return number mixed
	 */
	public static function getDomainLtd($domain, $config = array())
	{
		$config || $config = \core\Config::item('newTld')->toArray();
		$domLtd = self::getDomainClass($domain);
		if(self::isCnDomain($domain))
		{
			if(($domLtd == 'CN'))
			{
				return 6;
			}
			else 
				if($domLtd == '中国')
				{
					$domain = self::getDomainBody($domain);
					if(preg_match("/([\x{4e00}-\x{9fa5}])+/ui", $domain))
					{
						return 24;
					}
					return 26; // 英文.中国
				}
		}
		$domainLtd = self::getDomainClassAll($domain);
		$key = array_search($domainLtd, $config);
		if($key !== false)
		{
			return $key;
		}
		return 23; // otherCN
	}

	/**
	 * 获取域名最后一级后缀
	 *
	 * @param string $domain
	 */
	public static function getDomainClass($domain)
	{
		$domainArr = explode('.', $domain);
		return strtoupper($domainArr[count($domainArr) - 1]);
	}

	/**
	 * checkDomain
	 * 检验域名是否格式正确
	 */
	public static function checkDomain($domain)
	{
		$ok = FALSE;
		if(preg_match("/^([\x{4e00}-\x{9fa5}]|[a-zA-Z0-9-])+(\.[a-z]{2,4})?\.([a-z]{2,4}|[\x{4e00}-\x{9fa5}]{2,2})$/ui", 
			$domain))
		{
			if(substr($domain, 0, 1) != '-') // 去掉-开头的域名 &&
			                                 // stripos($domain,'--')===FALSE
			{
				$ok = true;
			}
		}
		return $ok;
	}

	/**
	 * 获取域名分组
	 *
	 * @param string $domain
	 * @return array(class,two,three,长度,域名主体);
	 */
	public static function getDomainGroupAll($domain)
	{
		$domainBody = self::getDomainBody($domain);
		$class = self::getClass($domainBody);
		$two = self::getTwoClass($class, $domainBody);
		$three = self::getThreeClass($class, $domainBody);
		return array($class,$two,$three,mb_strlen($domainBody, 'utf8'),$domainBody);
	}

	/**
	 *
	 * @param string $domainBody
	 * @return number
	 */
	private static function getClass($domainBody)
	{
		$class = 3;
		if(preg_match("/^\d+$/", $domainBody))
		{
			$class = 1;
		}
		elseif(self::isCnBody($domainBody))
		{
			$class = 4;
		}
		elseif(preg_match("/^[a-z]+$/", $domainBody))
		{
			$class = 2;
		}
		return $class;
	}

	/**
	 * 查看一个域名的主体是否包含中文
	 *
	 * @param string $domain
	 * @return boolean
	 */
	public static function isCnBody($domain)
	{
		if(preg_match("/[\x{4e00}-\x{9fa5}]+/u", self::getDomainBody($domain)))
		{
			return TRUE;
		}
		return FALSE;
	}

	private static function getTwoClass($class, $domainBody)
	{
		$two = 0;
		if(2 == $class)
		{
			if(! preg_match("/[a|e|e|i|o|u|v]+/", $domainBody))
			{
				$two = 6;
			}
			else
			{
				$py = self::pinyinSplit($domainBody);
				if($py)
				{
					$two = count($py);
					$two = $two > 4? 0: $two;
				}
				if(4 == strlen($domainBody))
				{
					$domainBodyArr = str_split($domainBody);
					if(preg_match("/[^aeiou]+/", $domainBodyArr[0] . $domainBodyArr[2]))
					{
						if(preg_match("/[a|e|i|o|u]+/", $domainBodyArr[1] . $domainBodyArr[3]))
						{
							$two = $two == 2? 12: 10; // 12：如果同时是CVCV和双拼
						}
					}
				}
			}
		}
		elseif(3 == $class)
		{
			$two = 8;
		}
		return $two;
	}

	public static function getThreeClass($class, $domainBody)
	{
		$len = strlen($domainBody);
		$dnArr = str_split($domainBody);
		$unArr = array_values(array_unique($dnArr));
		$unCount = count(array_unique($dnArr)); // 数字里面有多少个唯一的数组
		if($class < 3) // 只处理数字和字母
		{
			if($unCount > 3 || ($len < 4 && $unCount == 3))
			{
				return 0; // 目前不处理唯一字符还有4个的情况
			}
			$code = '';
			foreach($dnArr as $v)
			{
				$code .= (string)array_search($v, $unArr) + 1;
			}
			return $code;
		}
		if(3 == $class && 3 == $len)
		{
			$code = '';
			foreach($dnArr as $v)
			{
				if(is_numeric($v))
				{
					$code .= '1'; // 数字 1 字母 2
				}
				else
				{
					$code .= '2';
				}
			}
			return $code;
		}
		return 0;
	}

	/**
	 * 获取domain后缀对应key值
	 * 
	 * @param $domain
	 * @return bool int
	 */
	public static function tldValue($domain)
	{
		$temp = explode('.', $domain);
		$tld = str_replace("{$temp[0]}", '', $domain);
		$domaintldArr = \core\Config::item('ts_domaintld');
		foreach($domaintldArr as $k => $v)
		{
			if($v == $tld)
			{
				return $k;
			}
		}
		return false;
	}
}