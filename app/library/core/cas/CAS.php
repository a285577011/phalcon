<?php
require_once 'CAS/Client.php';

class phpCAS
{

	/**
	 * This variable is used by the interface class phpCAS.
	 *
	 * @hideinitializer
	 */
	private static $CasClient;

	/**
	 * This variable is used to store where the initializer is called from
	 * (to print a comprehensive error in case of multiple calls).
	 *
	 * @hideinitializer
	 */
	private static $phpCasInitCall;

	/**
	 * This variable is used to store phpCAS debug mode.
	 *
	 * @hideinitializer
	 */
	private static $PhpCasDebug;
	
	// ########################################################################
	// INITIALIZATION
	// ########################################################################
	
	/**
	 * phpCAS client initializer.
	 *
	 * @param string $serverHostname the hostname of the CAS server
	 * @param string $serverPort the port the CAS server is running on
	 * @param string $serverUri the URI the CAS server is responding on
	 * @param bool $changeSessionID Allow phpCAS to change the session_id
	 * (Single
	 * Sign Out/handleLogoutRequests is based on that change)
	 *
	 * @return a newly created CAS_Client object
	 * @note Only one of the phpCAS::client() and phpCAS::proxy functions should
	 * be
	 * called, only once, and before all other methods (except
	 * phpCAS::getVersion()
	 * and phpCAS::setDebug()).
	 */
	public static function client($serviceId, $serverHostname, $serverPort, $serverUri, $caCertPath = '')
	{
		phpCAS::traceBegin();
		if(is_object(self::$CasClient))
		{
			phpCAS::error(
				self::$phpCasInitCall['method'] . '() has already been called (at ' . self::$phpCasInitCall['file'] . ':' .
					 self::$phpCasInitCall['line'] . ')');
		}
		if(empty($serverHostname))
		{
			phpCAS::error('type mismatched for parameter $server_hostname (should be `string\')');
		}
		if(empty($serviceId) || ! is_numeric($serviceId))
		{
			phpCAS::error('type mismatched for parameter $serviceId (should be `integer\')');
		}
		if(empty($serverPort) || ! is_numeric($serverPort))
		{
			phpCAS::error('type mismatched for parameter $serverPort (should be `integer\')');
		}
		
		// store where the initializer is called from
		$dbg = debug_backtrace();
		self::$phpCasInitCall = array('done'=> true,'file'=> $dbg[0]['file'],'line'=> $dbg[0]['line'],
				'method'=> __CLASS__ . '::' . __FUNCTION__);
		
		// initialize the object $CasClient
		return self::$CasClient = new CasClient($serviceId, $serverHostname, $serverPort, $serverUri, $caCertPath);
		phpCAS::traceEnd();
	}

	/**
	 * Set/unset debug mode
	 *
	 * @param string $filename the name of the file used for logging, or false
	 * to stop debugging.
	 *
	 * @return void
	 */
	public static function setDebug($filename = '')
	{
		if($filename != false && gettype($filename) != 'string')
		{
			phpCAS::error('type mismatched for parameter $dbg (should be false or the name of the log file)');
		}
		if($filename === false)
		{
			self::$PhpCasDebug['filename'] = false;
		}
		else
		{
			if(empty($filename))
			{
				if(preg_match('/^Win.*/', getenv('OS')))
				{
					if(isset($_ENV['TMP']))
					{
						$debugDir = $_ENV['TMP'] . '/';
					}
					else
					{
						$debugDir = '';
					}
				}
				else
				{
					$debugDir = '/tmp/';
				}
				$filename = $debugDir . 'phpCAS.log';
			}
			
			if(empty(self::$PhpCasDebug['unique_id']))
			{
				self::$PhpCasDebug['unique_id'] = substr(strtoupper(md5(uniqid(''))), 0, 4);
			}
			
			self::$PhpCasDebug['filename'] = $filename;
			self::$PhpCasDebug['indent'] = 0;
			
			phpCAS::trace('START phpCAS ******************');
		}
	}

	/**
	 * Logs a string in debug mode.
	 *
	 * @param string $str the string to write
	 *
	 * @return void @private
	 */
	public static function log($str)
	{
		$indent_str = ".";
		
		if(! empty(self::$PhpCasDebug['filename']))
		{
			// Check if file exists and modifiy file permissions to be only
			// readable by the webserver
			if(! file_exists(self::$PhpCasDebug['filename']))
			{
				touch(self::$PhpCasDebug['filename']);
				// Chmod will fail on windows
				@chmod(self::$PhpCasDebug['filename'], 0600);
			}
			for($i = 0; $i < self::$PhpCasDebug['indent']; $i++)
			{
				
				$indent_str .= '|    ';
			}
			// allow for multiline output with proper identing. Usefull for
			// dumping cas answers etc.
			$str2 = str_replace("\n", "\n" . self::$PhpCasDebug['unique_id'] . ' ' . $indent_str, $str);
			$nowTime = date('Y-m-d H:i:s');
			error_log($nowTime . ' ' . self::$PhpCasDebug['unique_id'] . ' ' . $indent_str . $str2 . "\n", 3, 
				self::$PhpCasDebug['filename']);
		}
	}

	/**
	 * This method is used by interface methods to print an error and where the
	 * function was originally called from.
	 *
	 * @param string $msg the message to print
	 *
	 * @return void @private
	 */
	public static function error($msg)
	{
		if(! empty(self::$PhpCasDebug['filename']))
		{
			$dbg = debug_backtrace();
			$function = '?';
			$file = '?';
			$line = '?';
			if(is_array($dbg))
			{
				for($i = 1; $i < sizeof($dbg); $i++)
				{
					if(is_array($dbg[$i]) && isset($dbg[$i]['class']))
					{
						if($dbg[$i]['class'] == __CLASS__)
						{
							$function = $dbg[$i]['function'];
							$file = $dbg[$i]['file'];
							$line = $dbg[$i]['line'];
						}
					}
				}
			}
			echo "<br />\n<b>phpCAS error</b>: <font color=\"FF0000\"><b>" . __CLASS__ . "::" . $function . '(): ' .
				 htmlentities($msg) . "</b></font> in <b>" . $file . "</b> on line <b>" . $line . "</b><br />\n";
		}
		else
		{
			echo "<br />\n<b>error</b>: <font color=\"FF0000\"><b>" . htmlentities($msg) . "</b></font><br />\n";
		}
		phpCAS::trace($msg);
		phpCAS::traceEnd();
	}

	/**
	 * This method is used to log something in debug mode.
	 *
	 * @param string $str string to log
	 *
	 * @return void
	 */
	public static function trace($str)
	{
		$dbg = debug_backtrace();
		phpCAS::log($str . ' [' . basename($dbg[0]['file']) . ':' . $dbg[0]['line'] . ']');
	}

	/**
	 * This method is used to indicate the start of the execution of a function
	 * in debug mode.
	 *
	 * @return void
	 */
	public static function traceBegin()
	{
		$dbg = debug_backtrace();
		$str = '=> ';
		if(! empty($dbg[1]['class']))
		{
			$str .= $dbg[1]['class'] . '::';
		}
		$str .= $dbg[1]['function'] . '(';
		if(is_array($dbg[1]['args']))
		{
			foreach($dbg[1]['args'] as $index => $arg)
			{
				if($index != 0)
				{
					$str .= ', ';
				}
				if(is_object($arg))
				{
					$str .= get_class($arg);
				}
				else
				{
					$str .= str_replace(array("\r\n","\n","\r"), "", var_export($arg, true));
				}
			}
		}
		if(isset($dbg[1]['file']))
		{
			$file = basename($dbg[1]['file']);
		}
		else
		{
			$file = 'unknown_file';
		}
		if(isset($dbg[1]['line']))
		{
			$line = $dbg[1]['line'];
		}
		else
		{
			$line = 'unknown_line';
		}
		$str .= ') [' . $file . ':' . $line . ']';
		phpCAS::log($str);
		if(! isset(self::$PhpCasDebug['indent']))
		{
			self::$PhpCasDebug['indent'] = 0;
		}
		else
		{
			self::$PhpCasDebug['indent']++;
		}
	}

	/**
	 * This method is used to indicate the end of the execution of a function in
	 * debug mode.
	 *
	 * @param string $res the result of the function
	 *
	 * @return void
	 */
	public static function traceEnd($res = '')
	{
		if(empty(self::$PhpCasDebug['indent']))
		{
			self::$PhpCasDebug['indent'] = 0;
		}
		else
		{
			self::$PhpCasDebug['indent']--;
		}
		$dbg = debug_backtrace();
		$str = '';
		if(is_object($res))
		{
			$str .= '<= ' . get_class($res);
		}
		else
		{
			$str .= '<= ' . str_replace(array("\r\n","\n","\r"), "", var_export($res, true));
		}
		
		phpCAS::log($str);
	}

	/**
	 * This method is used to indicate the end of the execution of the program
	 *
	 * @return void
	 */
	public static function traceExit()
	{
		phpCAS::log('exit()');
		while(self::$PhpCasDebug['indent'] > 0)
		{
			phpCAS::log('-');
			self::$PhpCasDebug['indent']--;
		}
	}

	/**
	 * @}
	 */
	// ########################################################################
	// AUTHENTICATION
	// ########################################################################
	/**
	 * Set the times authentication will be cached before really accessing the
	 * CAS server in gateway mode:
	 * - -1: check only once, and then never again (until you pree login)
	 * - 0: always check
	 * - n: check every "n" time
	 *
	 * @param int $n an integer.
	 *
	 * @return void
	 */
	public static function setCacheTimesForAuthRecheck($n)
	{
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should not be called before ' . __CLASS__ . '::client() or ' . __CLASS__ . '::proxy()');
		}
		if(gettype($n) != 'integer')
		{
			phpCAS::error('type mismatched for parameter $n (should be `integer\')');
		}
		self::$CasClient->setCacheTimesForAuthRecheck($n);
	}

	/**
	 * This method is called to check if the user is already authenticated
	 * locally or has a global cas session.
	 * A already existing cas session is
	 * determined by a cas gateway call.(cas login call without any interactive
	 * prompt)
	 *
	 * @return true when the user is authenticated, false when a previous
	 * gateway login failed or the function will not return if the user is
	 * redirected to the cas server for a gateway login attempt
	 */
	public static function checkAuthentication()
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should not be called before ' . __CLASS__ . '::client() or ' . __CLASS__ . '::proxy()');
		}
		
		$auth = self::$CasClient->checkAuthentication();
		
		phpCAS::traceEnd($auth);
		return $auth;
	}

	/**
	 * This method is called to force authentication if the user was not already
	 * authenticated.
	 * If the user is not authenticated, halt by redirecting to
	 * the CAS server.
	 *
	 * @return bool Authentication
	 */
	public static function forceAuthentication()
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should not be called before ' . __CLASS__ . '::client() or ' . __CLASS__ . '::proxy()');
		}
		$auth = self::$CasClient->forceAuthentication();
		phpCAS::traceEnd();
		return $auth;
	}

	/**
	 * This method is called to check if the user is authenticated (previously
	 * or by
	 * tickets given in the URL).
	 *
	 * @return true when the user is authenticated.
	 */
	public static function isAuthenticated()
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should not be called before ' . __CLASS__ . '::client() or ' . __CLASS__ . '::proxy()');
		}
		
		// call the isAuthenticated method of the $CasClient object
		$auth = self::$CasClient->isAuthenticated();
		
		phpCAS::traceEnd($auth);
		return $auth;
	}

	/**
	 * This method is called to check if the user is logined
	 *
	 * @return true when the user is Logined.
	 */
	public static function isLogined()
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should not be called before ' . __CLASS__ . '::client() or ' . __CLASS__ . '::proxy()');
		}
		
		// call the isAuthenticated method of the $CasClient object
		$auth = self::$CasClient->isLogined();
		
		phpCAS::traceEnd($auth);
		return $auth;
	}

	/**
	 * Checks whether authenticated based on $_SESSION.
	 * Useful to avoid
	 * server calls.
	 *
	 * @return bool true if authenticated, false otherwise.
	 * @since 0.4.22 by Brendan Arnold
	 */
	public static function isSessionAuthenticated()
	{
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should not be called before ' . __CLASS__ . '::client() or ' . __CLASS__ . '::proxy()');
		}
		return (self::$CasClient->isSessionAuthenticated());
	}

	/**
	 * This method returns the CAS user's login name.
	 *
	 * @return string the login name of the authenticated user
	 * @warning should not be called only after phpCAS::forceAuthentication().
	 *
	 */
	public static function getUser()
	{
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should not be called before ' . __CLASS__ . '::client() or ' . __CLASS__ . '::proxy()');
		}
		// if (!self::$CasClient->wasAuthenticationCalled()) {
		// phpCAS :: error('this method should only be called after ' .
		// __CLASS__ . '::forceAuthentication() or ' . __CLASS__ .
		// '::isAuthenticated()');
		// }
		// if (!self::$CasClient->wasAuthenticationCallSuccessful()) {
		// phpCAS :: error('authentication was checked (by ' .
		// self::$CasClient->getAuthenticationCallerMethod() . '() at ' .
		// self::$CasClient->getAuthenticationCallerFile() . ':' .
		// self::$CasClient->getAuthenticationCallerLine() . ') but the method
		// returned false');
		// }
		return self::$CasClient->getUser();
	}

	/**
	 * Handle logout requests.
	 *
	 * @param bool $check_client additional safety check
	 * @param array $allowed_clients array of allowed clients
	 *
	 * @return void
	 */
	public static function handleLogoutRequests($check_client = true)
	{
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should not be called before ' . __CLASS__ . '::client() or ' . __CLASS__ . '::proxy()');
		}
		return self::$CasClient->handleLogoutRequests($check_client);
	}

	/**
	 * This method returns the URL to be used to login.
	 * or phpCAS::isAuthenticated().
	 *
	 * @return the login name of the authenticated user
	 */
	public static function getServerLoginURL()
	{
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should not be called before ' . __CLASS__ . '::client() or ' . __CLASS__ . '::proxy()');
		}
		return self::$CasClient->getServerLoginURL();
	}

	/**
	 * Set the login URL of the CAS server.
	 *
	 * @param string $url the login URL
	 *
	 * @return void
	 * @since 0.4.21 by Wyman Chan
	 */
	public static function setServerLoginURL($url = '')
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error('this method should only be called after' . __CLASS__ . '::client()');
		}
		if(gettype($url) != 'string')
		{
			phpCAS::error('type mismatched for parameter $url (should be `string`)');
		}
		self::$CasClient->setServerLoginURL($url);
		phpCAS::traceEnd();
	}

	/**
	 * Set the serviceValidate URL of the CAS server.
	 * Used only in CAS 1.0 validations
	 *
	 * @param string $url the serviceValidate URL
	 *
	 * @return void
	 */
	public static function setServerServiceValidateURL($url = '')
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error('this method should only be called after' . __CLASS__ . '::client()');
		}
		if(gettype($url) != 'string')
		{
			phpCAS::error('type mismatched for parameter $url (should be `string`)');
		}
		self::$CasClient->setServerServiceValidateURL($url);
		phpCAS::traceEnd();
	}

	/**
	 * This method returns the URL to be used to login.
	 * or phpCAS::isAuthenticated().
	 *
	 * @return the login name of the authenticated user
	 */
	public static function getServerLogoutURL()
	{
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should not be called before ' . __CLASS__ . '::client() or ' . __CLASS__ . '::proxy()');
		}
		return self::$CasClient->getServerLogoutURL();
	}

	/**
	 * Set the logout URL of the CAS server.
	 *
	 * @param string $url the logout URL
	 *
	 * @return void
	 * @since 0.4.21 by Wyman Chan
	 */
	public static function setServerLogoutURL($url = '')
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error('this method should only be called after' . __CLASS__ . '::client()');
		}
		if(gettype($url) != 'string')
		{
			phpCAS::error('type mismatched for parameter $url (should be `string`)');
		}
		self::$CasClient->setServerLogoutURL($url);
		phpCAS::traceEnd();
	}

	/**
	 * Set the LoginKey URL of the CAS server.
	 *
	 * @param string $url the GetLoginKey URL
	 *
	 * @return void
	 */
	public static function setGetLoginKeyUrl($url = '')
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error('this method should only be called after' . __CLASS__ . '::client()');
		}
		if(gettype($url) != 'string')
		{
			phpCAS::error('type mismatched for parameter $url (should be `string`)');
		}
		self::$CasClient->setGetLoginKeyUrl($url);
		phpCAS::traceEnd();
	}

	/**
	 * Get the Login Key from the CAS server.
	 *
	 * @param string $url the GetLoginKey URL
	 *
	 * @return void
	 */
	public static function getLoginKey()
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error('this method should only be called after' . __CLASS__ . '::client()');
		}
		return self::$CasClient->getLoginKey();
		phpCAS::traceEnd();
	}

	/**
	 * This method is used to set the md5Key of the CAS server.
	 * 
	 * @param string $md5Key the md5Key
	 */
	public static function setMd5Key($md5Key)
	{
		if(empty($md5Key))
		{
			throw new Exception('md5Key is empty');
		}
		return self::$CasClient->setMd5Key($md5Key);
	}

	/**
	 * This method is used to logout from CAS.
	 *
	 * @param string $params an array that contains the optional url and
	 * service parameters that will be passed to the CAS server
	 *
	 * @return void
	 */
	public static function logout($params = "")
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should only be called after ' . __CLASS__ . '::client() or' . __CLASS__ . '::proxy()');
		}
		$parsedParams = array();
		if($params != "")
		{
			if(is_string($params))
			{
				phpCAS::error(
					'method `phpCAS::logout($url)\' is now deprecated, use `phpCAS::logoutWithUrl($url)\' instead');
			}
			if(! is_array($params))
			{
				phpCAS::error('type mismatched for parameter $params (should be `array\')');
			}
			foreach($params as $key => $value)
			{
				if($key != "service" && $key != "url")
				{
					phpCAS::error(
						'only `url\' and `service\' parameters are allowed for method `phpCAS::logout($params)\'');
				}
				$parsedParams[$key] = $value;
			}
		}
		self::$CasClient->logout($parsedParams);
		// never reached
		phpCAS::traceEnd();
	}

	/**
	 * This method is used to logout from CAS.
	 * Halts by redirecting to the CAS
	 * server.
	 *
	 * @param service $service a URL that will be transmitted to the CAS server
	 *
	 * @return void
	 */
	public static function logoutWithRedirectService($service)
	{
		phpCAS::traceBegin();
		if(! is_object(self::$CasClient))
		{
			phpCAS::error(
				'this method should only be called after ' . __CLASS__ . '::client() or' . __CLASS__ . '::proxy()');
		}
		if(! is_string($service))
		{
			phpCAS::error('type mismatched for parameter $service (should be `string\')');
		}
		self::$CasClient->logout(array("service"=> $service));
		// never reached
		phpCAS::traceEnd();
	}

	/**
	 * Get the URL that is set as the CAS service parameter.
	 *
	 * @return string Service Url
	 */
	public static function getServiceURL()
	{
		if(! is_object(self::$CasClient))
		{
			phpCAS::error('this method should only be called after ' . __CLASS__ . '::proxy()');
		}
		return (self::$CasClient->getURL());
	}
}
?>
