<?php
namespace lib\ad;
use \core\ModelBase;

class AdvertLib
{

	/**
	 * 解析JSON格式
	 * 
	 * @param unknown $Json
	 * @return multitype:unknown mixed |mixed|boolean
	 */
	public function coverJson($Json)
	{
		if($Json)
		{
			$array = json_decode($Json, true);
			if(isset($array['start']) && isset($array['end']))
			{
				return array($array['start'],$array['end']);
			}
			elseif(isset($array['start']))
			{
				return $array['start'];
			}
			return false;
		}
		return false;
	}

	/**
	 * 系统加密方法
	 *
	 * @param string $data 要加密的字符串
	 * @param string $key 加密密钥
	 * @return string
	 * @author huangyy
	 */
	public function encrypt($data, $key = '')
	{
		$key = md5(empty($key)? \core\Config::item('encrypt_key'): $key);
		$data = base64_encode($data);
		$x = 0;
		$len = strlen($data);
		$l = strlen($key);
		$char = '';
		
		for($i = 0; $i < $len; $i++)
		{
			if($x == $l)
				$x = 0;
			$char .= substr($key, $x, 1);
			$x++;
		}
		$str = '';
		
		for($i = 0; $i < $len; $i++)
		{
			$str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
		}
		return str_replace(array('+','/','='), array('-','_',''), base64_encode($str));
	}

	/**
	 * 系统解密方法
	 *
	 * @param string $data 要解密的字符串
	 * @param string $key 加密密钥
	 * @return string
	 * @author huangyy
	 */
	public function decrypt($data, $key = '')
	{
		$key = md5(empty($key)? \core\Config::item('encrypt_key'): $key);
		$data = str_replace(array('-','_'), array('+','/'), $data);
		$mod4 = strlen($data) % 4;
		if($mod4)
		{
			$data .= substr('====', $mod4);
		}
		$data = base64_decode($data);
		$x = 0;
		$len = strlen($data);
		$l = strlen($key);
		$char = $str = '';
		
		for($i = 0; $i < $len; $i++)
		{
			if($x == $l)
				$x = 0;
			$char .= substr($key, $x, 1);
			$x++;
		}
		
		for($i = 0; $i < $len; $i++)
		{
			if(ord(substr($data, $i, 1)) < ord(substr($char, $i, 1)))
			{
				$str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
			}
			else
			{
				$str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
			}
		}
		return base64_decode($str);
	}

	/**
	 * 解析分销ID
	 * 
	 * @param unknown $data
	 * @return multitype:NULL |boolean
	 */
	public function coverAgentId($data)
	{
		$id = array();
		if($data)
		{
			foreach($data as $val)
			{
				$id[] = $val->AgentId;
			}
			return $id;
		}
		return false;
	}

	/**
	 * 检查加密数组
	 * 
	 * @param unknown $str
	 * @return multitype:unknown multitype:
	 */
	public function checkArr($str,$dispatcher)
	{
		if(! $str || count(explode('_-', $str)) != 3)
		{
			return $dispatcher->forward(array('controller'=> 'Common','action'=> 'urlFail'));
			exit();
		}
		list($posId, $agentId, $type) = explode('_-', $str);
		return array($posId,$agentId,$type);
	}
}