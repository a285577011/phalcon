<?php
/**
 * The CAS_Client class is a client interface that provides CAS authentication.
 *
 * @class CAS_Client
 */
require_once 'Request/CurlRequest.php';

class CasClient
{
	private $server = array('serviceId'=> 0,'hostname'=> '','port'=> 80,'uri'=> '','baseUrl'=> '','loginUrl'=> '',
			'serviceValidateUrl'=> '','logoutUrl'=> '','md5Key'=> '','expiredTime'=> 300,'caCertPath'=> '');

	private $user = ''; // 登录后的用户信息
	
	/**
	 * CAS_Client constructor.
	 *
	 * @param string $serverHostname the hostname of the CAS server
	 * @param int $serverPort the port the CAS server is running on
	 * @param string $serverUri the URI the CAS server is responding on
	 *
	 * @return a newly created CAS_Client object
	 */
	public function __construct($serviceId, $serverHostname, $serverPort, $serverUri, $caCertPath = '')
	{
		phpCAS::traceBegin();
		
		// skip Session Handling for logout requests and if don't want it'
		if(session_id() == "")
		{
			phpCAS::trace("Starting a new session");
			session_start();
		}
		// check serviceId
		if($serviceId == 0 || ! is_numeric($serviceId))
		{
			phpCAS::error('bad CAS service Id');
		}
		$this->server['serviceId'] = $serviceId;
		
		// check hostname
		if(empty($serverHostname) || ! preg_match('/[\.\d\-a-z]*/', $serverHostname))
		{
			phpCAS::error('bad CAS server hostname (`' . $serverHostname . '\')');
		}
		$this->server['hostname'] = $serverHostname;
		
		// check port
		if($serverPort == 0 || ! is_int($serverPort))
		{
			phpCAS::error('bad CAS server port (`' . $serverHostname . '\')');
		}
		$this->server['port'] = $serverPort;
		
		// check URI
		if(! preg_match('/[\.\d\-_a-z\/]*/', $serverUri))
		{
			phpCAS::error('bad CAS server URI (`' . $serverUri . '\')');
		}
		// add leading and trailing `/' and remove doubles
		$serverUri = preg_replace('/\/\//', '/', '/' . $serverUri . '/');
		$this->server['uri'] = $serverUri;
		
		if($caCertPath)
		{
			$this->server['caCertPath'] = $caCertPath;
		}
		
		// normal mode: get ticket and remove it from CGI parameters for
		// developers
		$ticket = (isset($_GET['st'])? $_GET['st']: null);
		if(preg_match('/^[SP]T-/', $ticket))
		{
			phpCAS::trace('Ticket \'' . $ticket . '\' found');
			$this->setTicket($ticket);
			unset($_GET['st']);
		}
		else 
			if(! empty($ticket))
			{
				// ill-formed ticket, halt
				phpCAS::error('ill-formed ticket found in the URL (ticket=`' . htmlentities($ticket) . '\')');
			}
		phpCAS::traceEnd();
	}

	/**
	 * This method is used to retrieve the hostname of the CAS server.
	 *
	 * @return string the hostname of the CAS server.
	 */
	private function _getServerHostname()
	{
		return $this->server['hostname'];
	}

	/**
	 * This method is used to retrieve the port of the CAS server.
	 *
	 * @return string the port of the CAS server.
	 */
	private function _getServerPort()
	{
		return $this->server['port'];
	}

	/**
	 * This method is used to retrieve the URI of the CAS server.
	 *
	 * @return string a URI.
	 */
	private function _getServerURI()
	{
		return $this->server['uri'];
	}

	/**
	 * This method is used to get md5 key from the CAS server.
	 *
	 * @return string a URI.
	 */
	private function _getMd5Key()
	{
		return $this->server['md5Key'];
	}

	/**
	 * This method is used to set the md5Key of the CAS server.
	 * 
	 * @param string $md5Key the md5Key
	 */
	public function setMd5Key($md5Key)
	{
		return $this->server['md5Key'] = $md5Key;
	}

	/**
	 * This method is used to retrieve the base URL of the CAS server.
	 *
	 * @return string a URL.
	 */
	private function _getServerBaseURL()
	{
		// the URL is build only when needed
		if(empty($this->server['baseUrl']))
		{
			$httpString = $this->server['port'] == 80? 'http://': 'https://';
			$this->server['baseUrl'] = $httpString . $this->_getServerHostname();
			if($this->_getServerPort() != 443 and $this->_getServerPort() != 80)
			{
				$this->server['baseUrl'] .= ':' . $this->_getServerPort();
			}
			$this->server['baseUrl'] .= $this->_getServerURI();
		}
		return $this->server['baseUrl'];
	}

	/**
	 * This method is used to retrieve the login URL of the CAS server.
	 *
	 * @return a URL.
	 */
	public function getServerLoginURL()
	{
		phpCAS::traceBegin();
		// the URL is build only when needed
		if(empty($this->server['loginUrl']))
		{
			$this->server['loginUrl'] = $this->_getServerBaseURL();
			$this->server['loginUrl'] .= 'login?sid=' . $this->server['serviceId'] . '&backurl=';
			if(empty($_GET['backurl']))
			{
				$backurl = $this->getURL();
			}
			else
			{
				$backurl = $_GET['backurl'];
			}
			if(empty($backurl))
			{
				$backurl = $this->getURL();
			}
			
			$this->server['loginUrl'] .= urlencode($backurl);
		}
		$url = $this->server['loginUrl'];
		phpCAS::traceEnd($url);
		return $url;
	}

	public function getHttpReferer()
	{
		if(isset($_SERVER['HTTP_REFERER']))
		{
			return $_SERVER['HTTP_REFERER'];
		}
		return '';
	}

	/**
	 * This method is used to retrieve the getloginkey URL of the CAS server.
	 *
	 * @return a URL.
	 */
	public function getLoginKeyURL()
	{
		phpCAS::traceBegin();
		// the URL is build only when needed
		if(empty($this->server['loginKeyUrl']))
		{
			$this->server['loginKeyUrl'] = $this->_getServerBaseURL();
			$this->server['loginKeyUrl'] .= 'login/key?sid=' . $this->server['serviceId'] . '&action=getLoginKey';
		}
		$url = $this->server['loginKeyUrl'];
		phpCAS::traceEnd($url);
		return $url;
	}

	/**
	 * This method sets the login URL of the CAS server.
	 *
	 * @param string $url the login URL
	 *
	 * @return string login url
	 */
	public function setServerLoginURL($url)
	{
		return $this->server['loginUrl'] = $url;
	}

	/**
	 * This method sets the serviceValidate URL of the CAS server.
	 *
	 * @param string $url the serviceValidate URL
	 *
	 * @return string serviceValidate URL
	 */
	public function setServerServiceValidateURL($url)
	{
		return $this->server['serviceValidateUrl'] = $url;
	}

	/**
	 * This method is used to retrieve the service validating URL of the CAS
	 * server.
	 *
	 * @return string serviceValidate URL.
	 */
	public function getServerServiceValidateURL()
	{
		phpCAS::traceBegin();
		// the URL is build only when needed
		if(empty($this->server['serviceValidateUrl']))
		{
			$this->server['serviceValidateUrl'] = $this->_getServerBaseURL() . 'validate';
		}
		// $url = $this->_buildQueryUrl($this->server['serviceValidateUrl'],
		// 'service='.urlencode($this->getURL()));
		phpCAS::traceEnd($this->server['serviceValidateUrl']);
		return $this->server['serviceValidateUrl'];
	}

	/**
	 * This method is used to retrieve the logout URL of the CAS server.
	 *
	 * @return string logout URL.
	 */
	public function getServerLogoutURL()
	{
		// the URL is build only when needed
		if(empty($this->server['logoutUrl']))
		{
			$this->server['logoutUrl'] = $this->_getServerBaseURL() . 'logout';
		}
		return $this->server['logoutUrl'];
	}

	/**
	 * This method sets the logout URL of the CAS server.
	 *
	 * @param string $url the logout URL
	 *
	 * @return string logout url
	 */
	public function setServerLogoutURL($url)
	{
		return $this->server['logoutUrl'] = $url;
	}

	/**
	 * This method sets the GetLoginKey URL of the CAS server.
	 *
	 * @param string $url the GetLoginKey URL
	 *
	 * @return string GetLoginKey url
	 */
	public function setGetLoginKeyUrl($url)
	{
		return $this->server['loginKeyUrl'] = $url;
	}

	/**
	 * This method get the login Key from the CAS server.
	 *
	 * @return string GetLoginKey
	 */
	public function getLoginKey()
	{
		$loginKeyUrl = $this->getLoginKeyURL();
		// open and read the URL
		if(! $this->_readURL($loginKeyUrl, $headers, $textResponse, $errMsg))
		{
			phpCAS::trace('could not open URL \'' . $loginKeyUrl . '\' to validate (' . $errMsg . ')');
			return false;
		}
		$response = json_decode($textResponse, TRUE);
		if(! isset($response['key']) or empty($response['key']))
		{
			phpCAS::trace('ill-formed response:' . $textResponse);
			return false;
		}
		return $response['key'];
	}

	/**
	 * This method sets the CAS user's login name.
	 *
	 * @param string $user the login name of the authenticated user.
	 *
	 * @return void
	 */
	private function _setUser($user)
	{
		$this->user = $user;
	}

	/**
	 * This method returns the CAS user's login name.
	 *
	 * @return string the login name of the authenticated user
	 *
	 */
	public function getUser()
	{
		if(empty($this->user))
		{
			phpCAS::error(
				'this method should be used only after ' . __CLASS__ . '::forceAuthentication() or ' . __CLASS__ .
					 '::isAuthenticated()');
		}
		return $this->user;
	}

	/**
	 * This method is called to be sure that the user is authenticated.
	 * When not
	 * authenticated, halt by redirecting to the CAS server; otherwise return
	 * true.
	 *
	 * @return true when the user is authenticated; otherwise halt.
	 */
	public function forceAuthentication()
	{
		phpCAS::traceBegin();
		if($this->isAuthenticated())
		{
			// the user is authenticated, nothing to be done.
			phpCAS::trace('no need to authenticate');
			$res = true;
		}
		else
		{
			// the user is not authenticated, redirect to the CAS server
			if(isset($_SESSION['phpCAS']['authChecked']))
			{
				unset($_SESSION['phpCAS']['authChecked']);
			}
				$this->redirectToCas();
			// never reached
		}
		phpCAS::traceEnd($res);
		return $res;
	}

	/**
	 * An integer that gives the number of times authentication will be cached
	 * before rechecked.
	 *
	 * @hideinitializer
	 */
	private $_cache_times_for_auth_recheck = 0;

	/**
	 * Set the number of times authentication will be cached before rechecked.
	 *
	 * @param int $n number of times to wait for a recheck
	 *
	 * @return void
	 */
	public function setCacheTimesForAuthRecheck($n)
	{
		$this->_cache_times_for_auth_recheck = $n;
	}

	/**
	 * This method is called to check whether the user is authenticated or not.
	 *
	 * @return true when the user is authenticated, false when a previous
	 */
	public function checkAuthentication()
	{
		phpCAS::traceBegin();
		$res = false;
		if($this->isAuthenticated())
		{
			phpCAS::trace('user is authenticated');
			/* The 'authChecked' variable is removed just in case it's set. */
			unset($_SESSION['phpCAS']['authChecked']);
			$res = true;
		}
		else 
			if(isset($_SESSION['phpCAS']['authChecked']))
			{
				// the previous request has redirected the client to the CAS
				// server
				// with gateway=true
				unset($_SESSION['phpCAS']['authChecked']);
				$res = false;
			}
			else
			{
				// avoid a check against CAS on every request
				if(! isset($_SESSION['phpCAS']['unauthCount']))
				{
					$_SESSION['phpCAS']['unauthCount'] = - 2; // uninitialized
				}
				
				if(($_SESSION['phpCAS']['unauthCount'] != - 2 && $this->_cache_times_for_auth_recheck == - 1) || ($_SESSION['phpCAS']['unauthCount'] >=
					 0 && $_SESSION['phpCAS']['unauthCount'] < $this->_cache_times_for_auth_recheck))
				{
					$res = false;
					
					if($this->_cache_times_for_auth_recheck != - 1)
					{
						$_SESSION['phpCAS']['unauthCount']++;
						phpCAS::trace(
							'user is not authenticated (cached for ' . $_SESSION['phpCAS']['unauthCount'] . ' times of ' .
								 $this->_cache_times_for_auth_recheck . ')');
					}
					else
					{
						phpCAS::trace('user is not authenticated (cached for until login pressed)');
					}
				}
				else
				{
					$_SESSION['phpCAS']['unauthCount'] = 0;
					$_SESSION['phpCAS']['authChecked'] = true;
					phpCAS::trace('user is not authenticated (cache reset)');
					$this->redirectToCas();
					// never reached
					$res = false;
				}
			}
		phpCAS::traceEnd($res);
		return $res;
	}

	/**
	 * This method is called to check if the user is authenticated (previously
	 * or by
	 * tickets given in the URL).
	 *
	 * @return true when the user is authenticated. Also may redirect to the
	 * same URL without the ticket.
	 */
	public function isAuthenticated()
	{
		phpCAS::traceBegin();
		$res = false;
		$validate_url = '';
		if($this->hasTicket())
		{
			// if a Service Ticket was given, validate it
			phpCAS::trace('CAS 1.0 ticket `' . $this->getTicket() . '\' is present');
			if(! $this->validateCAS10($validate_url, $text_response, $tree_response)) // if
			                                                                         // it
			                                                                         // fails,
			                                                                         // it
			                                                                         // halts
			{
				throw new Exception('error ticket');
			}
			phpCAS::trace('CAS 1.0 ticket `' . $this->getTicket() . '\' was validated');
			$_SESSION['phpCAS']['user'] = $this->getUser();
			$res = true;
			$logoutTicket = $this->getTicket();
		}
		else
		{
			// no ticket given, not authenticated
			phpCAS::trace('no ticket found');
			if($this->isSessionAuthenticated())
			{
				$this->_setUser($_SESSION['phpCAS']['user']);
				return TRUE;
			}
		}
		phpCAS::traceEnd($res);
		return $res;
	}

	/**
	 * This method is called to check if the user is logined
	 *
	 * @return true when the user is Logined.
	 */
	public function isLogined()
	{
		phpCAS::traceBegin();
		$res = false;
		
		if($this->isSessionAuthenticated())
		{
			$this->_setUser($_SESSION['phpCAS']['user']);
			return TRUE;
		}
		else
		{
			$session_id = session_id();
			phpCAS::trace("no Login sessionId : " . $session_id);
			if(strpos($session_id, 'ST-'))
			{
				// 未登录，清楚下session
				session_unset();
				session_destroy();
				setcookie('PHPSESSID', NULL, time() - 86400);
			}
		}
		phpCAS::traceEnd($res);
		return $res;
	}

	/**
	 * This method tells if the current session is authenticated.
	 *
	 * @return true if authenticated based soley on $_SESSION variable
	 */
	public function isSessionAuthenticated()
	{
		return ! empty($_SESSION['phpCAS']['user']);
	}

	/**
	 * This method is used to redirect the client to the CAS server.
	 * It is used by CAS_Client::forceAuthentication().
	 *
	 * @param bool $gateway true to check authentication, false to force it
	 * @param bool $renew true to force the authentication with the CAS server
	 *
	 * @return void
	 */
	public function redirectToCas()
	{
		phpCAS::traceBegin();
		$cas_url = $this->getServerLoginURL();
		if(php_sapi_name() === 'cli')
		{
			echo '<script language="javascript">window.location.href="' . $cas_url . '";</script>';
			 //@header('Location: '.$cas_url);
		}
		else
		{
			echo '<script language="javascript">window.location.href="' . $cas_url . '";</script>';
		// header('Location: '.$cas_url);
		}
		phpCAS::trace("Redirect to : " . $cas_url);
		phpCAS::traceExit();
		exit();
	}

	/**
	 * This method is used to logout from CAS.
	 *
	 * @param array $params an array that contains the optional url and service
	 * parameters that will be passed to the CAS server
	 *
	 * @return void
	 */
	public function logout($params)
	{
		phpCAS::traceBegin();
		$cas_url = $this->getServerLogoutURL();
		$paramSeparator = '?';
		$url = 'http://'.$this->_getServerUrl();
		if(isset($params['backUrl']))
		{
			$params['backUrl'] = empty($params['backUrl']) ? $url : $params['backUrl'];
			$cas_url = $cas_url . $paramSeparator . 'sid=' . $this->server['serviceId'] . '&backurl=' .
				 urlencode($params['backUrl']);
			$paramSeparator = '&';
		}
		else
		{
			$cas_url = $cas_url . $paramSeparator . 'sid=' . $this->server['serviceId'] . '&backurl=' .
				 urlencode($this->getURL());
			$paramSeparator = '&';
		}
		// if (isset($params['service'])) {
		// $cas_url = $cas_url . $paramSeparator . "service=" .
		// urlencode($params['service']);
		// }
		//echo $cas_url;exit;
		phpCAS::trace("Prepare redirect to : " . $cas_url);
		header('Location: ' . $cas_url);
		if(session_id() !== "")
		{
			session_unset();
			session_destroy();
			setcookie('PHPSESSID', NULL, time() - 86400);
		}
		phpCAS::traceExit();
		return TRUE;
	}

	/**
	 * Check of the current request is a logout request
	 *
	 * @return bool is logout request.
	 */
	private function _isLogoutRequest()
	{
		return ! empty($_REQUEST['sessionId']);
	}

	/**
	 * This method handles logout requests.
	 *
	 * @param bool $check_client true to check the client bofore handling
	 * the request, false not to perform any access control. True by default.
	 *
	 * @return void
	 */
	public function handleLogoutRequests($check_client = true)
	{
		phpCAS::traceBegin();
		if(! $this->_isLogoutRequest())
		{
			phpCAS::trace("Not a logout request [20001]");
			phpCAS::traceEnd();
			return 20001; // 非退出请求
		}
		phpCAS::trace("Logout requested");
		
		phpCAS::trace('QueryUrl:http://' . $_SERVER['HTTP_HOST'] . '/' . $_SERVER['QUERY_STRING']);
		$expiredTime = urldecode(trim(empty($_REQUEST['expiredTime'])? '': $_REQUEST['expiredTime']));
		$sessionId = trim(empty($_REQUEST['sessionId'])? '': $_REQUEST['sessionId']);
		$sign = trim(empty($_REQUEST['sign'])? '': $_REQUEST['sign']);
		
		phpCAS::trace("SAML REQUEST: session_id:" . $sessionId . '/expiredTime:' . $expiredTime);
		$allowed = false;
		if($check_client)
		{
			$client_ip = $_SERVER['REMOTE_ADDR'];
			$client = gethostbyaddr($client_ip);
			phpCAS::trace("Client: " . $client . "/" . $client_ip);
			
			$nowTime = time();
			$startTime = $nowTime - $this->server['expiredTime'];
			$endTime = $nowTime + $this->server['expiredTime'];
			if($expiredTime < $startTime || $expiredTime > $endTime)
			{
				phpCAS::trace("Allowed client time out [20002] nowtime:" . $nowTime . ' exp:' . $expiredTime);
				$allowed = FALSE;
				return 20002; // 请求已过期
			}
			
			$makeSign = strtolower((md5($sessionId . $expiredTime . $this->_getMd5Key())));
			if($sign != $makeSign)
			{
				phpCAS::trace(
					"Allowed client does not match [20003] " . $sessionId . $expiredTime . ' ' . $sign . ' != ' .
						 $makeSign);
				$allowed = FALSE;
				return 20003; // 签名无效
			}
			else
			{
				phpCAS::trace("Allowed client  matches, logout request is allowed");
				$allowed = true;
			}
		}
		else
		{
			phpCAS::trace("No access control set");
			$allowed = true;
		}
		// If Logout command is permitted proceed with the logout
		if($allowed)
		{
			phpCAS::trace("Logout command allowed");
			// If phpCAS is managing the session_id, destroy session thanks to
			// session_id.
			phpCAS::trace("Session id: " . $sessionId);
			// destroy a possible application session created before phpcas
			if(session_id() !== "")
			{
				session_unset();
				session_destroy();
				setcookie('PHPSESSID', NULL, time() - 86400);
			}
			session_start();
			// fix session ID
			session_id($sessionId);
			$_COOKIE[session_name()] = $sessionId;
			$_GET[session_name()] = $sessionId;
			
			// Overwrite session
			session_unset();
			session_destroy();
			setcookie('PHPSESSID', NULL, time() - 86400);
			phpCAS::trace("Session " . $sessionId . " destroyed");
			flush();
			phpCAS::traceExit();
			return TRUE;
		}
		else
		{
			phpCAS::error("Unauthorized logout request from client '" . $client . "'");
			phpCAS::trace("Unauthorized logout request from client '" . $client . "'");
		}
		flush();
		phpCAS::traceExit();
		return 20004;
	}

	/**
	 * The Ticket provided in the URL of the request if present
	 * (empty otherwise).
	 * Written by CAS_Client::CAS_Client(), read by
	 * CAS_Client::getTicket() and CAS_Client::_hasPGT().
	 *
	 * @hideinitializer
	 */
	private $_ticket = '';

	/**
	 * This method returns the Service Ticket provided in the URL of the
	 * request.
	 *
	 * @return string service ticket.
	 */
	public function getTicket()
	{
		return $this->_ticket;
	}

	/**
	 * This method stores the Service Ticket.
	 *
	 * @param string $st The Service Ticket.
	 *
	 * @return void
	 */
	public function setTicket($st)
	{
		$this->_ticket = $st;
	}

	/**
	 * This method tells if a Service Ticket was stored.
	 *
	 * @return bool if a Service Ticket has been stored.
	 */
	public function hasTicket()
	{
		return ! empty($this->_ticket);
	}

	/**
	 * This method is used to validate a CAS 1,0 ticket; halt on failure, and
	 * sets $validate_url, $text_reponse and $tree_response on success.
	 *
	 * @param string &$validate_url reference to the the URL of the request to
	 * the CAS server.
	 * @param string &$text_response reference to the response of the CAS
	 * server, as is (XML text).
	 * @param string &$tree_response reference to the response of the CAS
	 * server, as a DOM XML tree.
	 *
	 * @return bool true when successfull and issue a
	 * CAS_AuthenticationException
	 * and false on an error
	 */
	public function validateCAS10(&$validate_url, &$text_response, &$tree_response)
	{
		phpCAS::traceBegin();
		$result = false;
		// build the URL to validate the ticket
		$validate_url = $this->getServerServiceValidateURL() . '?st=' . $this->getTicket() . '&f=fuck';
		// open and read the URL
		if(! $this->_readURL($validate_url, $headers, $text_response, $err_msg))
		{
			phpCAS::trace('could not open URL \'' . $validate_url . '\' to validate (' . $err_msg . ')');
			return false;
			$result = false;
		}
		$response = json_decode($text_response, TRUE);
		phpCAS::trace('ReturnInfo:' . $text_response);
		if(! isset($response['status']) or $response['status'] == '0')
		{
			phpCAS::trace('Ticket has not been validated:' . $text_response);
			return false;
		}
		if(! isset($response['info']))
		{
			phpCAS::trace('ill-formed response' . $text_response);
			return false;
		}
		$this->_setUser($response['info']);
		$result = true;
		if($result)
		{
			$this->_renameSession($this->getTicket());
		}
		// at this step, ticket has been validated and $this->user has been set,
		phpCAS::traceEnd(true);
		return true;
	}

	/**
	 *
	 *
	 * 读取url地址返回值
	 * 
	 * @param string $url
	 * @return string $headers
	 * @return string $body
	 * @return string $err_msg
	 */
	private function _readURL($url, &$headers, &$body, &$err_msg)
	{
		phpCAS::traceBegin();
		
		$request = new CAS_Request_CurlRequest();
		$request->setUrl($url);
		// 是否需要ssl
		IF($this->server['port'] == '443' and $this->server['caCertPath'])
		{
			
			$request->setCaCertPath($this->server['caCertPath']);
		}
		if($request->sendRequest())
		{
			$body = $request->getResponseBody();
			$err_msg = '';
			phpCAS::traceEnd(true);
			return true;
		}
		else
		{
			$headers = '';
			$body = '';
			$err_msg = $request->getErrorMessage();
			phpCAS::traceEnd(false);
			return false;
		}
	}

	/**
	 * the URL of the current request (without any ticket CGI parameter).
	 * Written
	 * and read by CAS_Client::getURL().
	 *
	 * @hideinitializer
	 */
	private $_url = '';

	/**
	 * This method sets the URL of the current request
	 *
	 * @param string $url url to set for service
	 *
	 * @return void
	 */
	public function setURL($url)
	{
		$this->_url = $url;
	}

	/**
	 * This method returns the URL of the current request (without any ticket
	 * CGI parameter).
	 *
	 * @return The URL
	 */
	public function getURL()
	{
		phpCAS::traceBegin();
		// the URL is built when needed only
		if(empty($this->_url))
		{
			$final_uri = '';
			// remove the ticket if present in the URL
			$final_uri = ($this->_isHttps())? 'https': 'http';
			$final_uri .= '://';
			
			$final_uri .= $this->_getServerUrl();
			$request_uri = explode('?', $_SERVER['REQUEST_URI'], 2);
			$final_uri .= $request_uri[0];
			
			if(isset($request_uri[1]) && $request_uri[1])
			{
				$query_string = $this->_removeParameterFromQueryString('st', $request_uri[1]);
				
				// If the query string still has anything left, append it to the
				// final URI
				if($query_string !== '')
				{
					$final_uri .= "?$query_string";
				}
			}
			
			phpCAS::trace("Final URI: $final_uri");
			$this->setURL($final_uri);
		}
		phpCAS::traceEnd($this->_url);
		return $this->_url;
	}

	/**
	 * Try to figure out the server URL with possible Proxys / Ports etc.
	 *
	 * @return string Server URL with domain:port
	 */
	private function _getServerUrl()
	{
		$server_url = '';
		if(! empty($_SERVER['HTTP_X_FORWARDED_HOST']))
		{
			// explode the host list separated by comma and use the first host
			$hosts = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
			$server_url = $hosts[0];
		}
		else 
			if(! empty($_SERVER['HTTP_X_FORWARDED_SERVER']))
			{
				$server_url = $_SERVER['HTTP_X_FORWARDED_SERVER'];
			}
			else
			{
				if(empty($_SERVER['SERVER_NAME']))
				{
					$server_url = $_SERVER['HTTP_HOST'];
				}
				else
				{
					$server_url = $_SERVER['SERVER_NAME'];
				}
			}
		if(! strpos($server_url, ':'))
		{
			if(($this->_isHttps() && $_SERVER['SERVER_PORT'] != 443) ||
				 (! $this->_isHttps() && $_SERVER['SERVER_PORT'] != 80))
			{
				$server_url .= ':';
				$server_url .= $_SERVER['SERVER_PORT'];
			}
		}
		return $server_url;
	}

	/**
	 * This method checks to see if the request is secured via HTTPS
	 *
	 * @return bool true if https, false otherwise
	 */
	private function _isHttps()
	{
		if(isset($_SERVER['HTTPS']) && ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Removes a parameter from a query string
	 *
	 * @param string $parameterName name of parameter
	 * @param string $queryString query string
	 *
	 * @return string new query string
	 *
	 */
	private function _removeParameterFromQueryString($parameterName, $queryString)
	{
		$parameterName = preg_quote($parameterName);
		return preg_replace("/&$parameterName(=[^&]*)?|^$parameterName(=[^&]*)?&?/", '', $queryString);
	}

	/**
	 * This method is used to append query parameters to an url.
	 * Since the url
	 * might already contain parameter it has to be detected and to build a
	 * proper
	 * URL
	 *
	 * @param string $url base url to add the query params to
	 * @param string $query params in query form with & separated
	 *
	 * @return url with query params
	 */
	private function _buildQueryUrl($url, $query)
	{
		$url .= (strstr($url, '?') === false)? '?': '&';
		$url .= $query;
		return $url;
	}

	/**
	 * Renaming the session
	 *
	 * @param string $ticket name of the ticket
	 *
	 * @return void
	 */
	private function _renameSession($ticket)
	{
		phpCAS::traceBegin();
		if(! empty($this->user))
		{
			phpCAS::trace("QUERY url: " . $_SERVER['HTTP_HOST'] . '/' . $_SERVER['QUERY_STRING']);
			$old_session = $_SESSION;
			if(session_id() !== "")
			{
				$oldSessionid = session_id();
				session_unset();
				session_destroy();
				if(strpos($_SERVER['HTTP_HOST'], 'ename.cn'))
				{
					setcookie('PHPSESSID', NULL, time() - 864000, '/', '.ename.cn');
				}
				else
				{
					setcookie('PHPSESSID', NULL, time() - 864000);
				}
				phpCAS::trace("Old Session ID: " . $oldSessionid);
			}
			// set up a new session, of name based on the ticket
			$sessionId = preg_replace('/[^a-zA-Z0-9\-]/', '', $ticket);
			phpCAS::trace("Session ID: " . $sessionId);
			session_id($sessionId);
			session_start();
			$newSessionid = session_id();
			phpCAS::trace("Restoring old session vars");
			phpCAS::trace("New session id:" . $newSessionid);
			$_SESSION = $old_session;
		}
		else
		{
			phpCAS::error('Session should only be renamed after successfull authentication');
		}
		phpCAS::traceEnd();
	}
}
?>
