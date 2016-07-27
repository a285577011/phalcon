<?php

class WebVisitor
{

	public static $loginEnameId=505863; // 临时在内存保存登录enameid;
	public static $enamePhpCAS; // 临时在内存保存登录enameid;
	public static function initPhpCAS()
	{
		if(is_object(self::$enamePhpCAS))
		{
			return self::$enamePhpCAS;
		}
		// @author hush 2012-06-07
		// Load the settings from the central config file
		require_once 'config.php';
		// Load the CAS lib
		// require_once $phpCasPath . '/CAS.php';
		require_once 'CAS.php';
		$logFileName = '/var/www/ename/logs/' . date('Y-m-d') . '_phpcas.log';
		phpCAS::setDebug(false);
		// // Initialize phpCAS
		self::$enamePhpCAS = phpCAS::client($casServiceId, $casHost, $casPort, $casContext, $caCertPath);
		phpCAS::setMd5Key($md5Key);
		return self::$enamePhpCAS;
	}
	public static function getIp()
	{
		if(defined('IS_CLI') && IS_CLI==TRUE)
		{
			return '0.0.0.0';
		}

		if(1==CDN_MODE && isset($_SERVER['HTTP_CF_CONNECTING_IP']))
		{
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		if(2==CDN_MODE)
		{
			if (getenv('HTTP_CLIENT_IP'))
			{
				$ip = getenv('HTTP_CLIENT_IP');
			}
			elseif (getenv('HTTP_X_FORWARDED_FOR'))
			{ //获取客户端用代理服务器访问时的真实ip 地址
				if (strpos(getenv('HTTP_X_FORWARDED_FOR'), ',') !== false)
				{
					$ips = explode(',', getenv('HTTP_X_FORWARDED_FOR'));
					$ip = trim($ips[count($ips) - 1]);
				}
				else
				{
					$ip = getenv('HTTP_X_FORWARDED_FOR');
				}
			}
			elseif (getenv('HTTP_X_FORWARDED'))
			{
				$ip = getenv('HTTP_X_FORWARDED');
			}
			elseif (getenv('HTTP_FORWARDED_FOR'))
			{
				$ip = getenv('HTTP_FORWARDED_FOR');
			}
			elseif (getenv('HTTP_FORWARDED'))
			{
				$ip = getenv('HTTP_FORWARDED');
			}
		}

		if(!isset($ip)||empty($ip))
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		if(!$ip)
		{
			return '';
		}
		return substr($ip, 0, 15);
	}
		
	public static function logout($backUrl)
	{
		self::initPhpCAS();
		phpCAS::logout(array('backUrl'=> $backUrl));
		return TRUE;
	}

	public static function callLogout()
	{
		self::initPhpCAS();
		$logoutCode = phpCAS::handleLogoutRequests(TRUE);
		return $logoutCode;
	}

	public static function forceLogin()
	{
		try
		{
			self::initPhpCAS();
			if(phpCAS::forceAuthentication())
			{
				return self::getEnameId();
			}
			return FALSE;
		}
		catch(Exception $e)
		{
			return FALSE;
		}
	}
	/**
	 * 检测用户是否登录使用 如果用户已经登录会返回ID 如果没有登录会跳到网关去登录
	 * 如果是在程序中获取用户ID请使用getEnameId()
	 * 
	 * @return int
	 */
	public static function checkLogin()
	{	
		$enameId = self::getEnameId();
		if($enameId)
		{
			return $enameId;
		}
		return self::forceLogin();
	}

	/**
	 * 获取EnameId
	 */
	public static function getEnameId()
	{
		if(self::$loginEnameId)
		{
			return self::$loginEnameId;
		}
		self::initPhpCAS();
		$isLogined = phpCAS::isLogined();
		if(empty($isLogined))
		{
			return FALSE;
		}
		$userInfo = phpCAS::getUser();
		if(! empty($userInfo['AdminName']))
		{
			$_SESSION['AdminName'] = $userInfo['AdminName'];
		}
		self::$loginEnameId = empty($userInfo['EnameId'])? FALSE: $userInfo['EnameId'];
		return self::$loginEnameId;
	}
}
?>
