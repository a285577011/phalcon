<?php

/**
 * Provides support for performing web-requests via curl
 */
class CAS_Request_CurlRequest
{

	private $url = '';

	private $_responseBody = '';

	private $_errorMessage = '';

	private $caCertPath = '';

	/**
	 *
	 *
	 * Enter description here ...
	 * 
	 * @param string $url
	 */
	public function setUrl($url)
	{
		$this->url = $url;
	}

	/**
	 *
	 *
	 * 设置ssl证书地址
	 * 
	 * @param string $url
	 */
	public function setCaCertPath($caCertPath)
	{
		$this->caCertPath = $caCertPath;
	}

	/**
	 * Send the request and store the results.
	 *
	 * @return bool true on success, false on failure.
	 */
	public function sendRequest()
	{
		phpCAS::traceBegin();
		$ch = curl_init($this->url);
		if(is_null($this->url) || ! $this->url)
		{
			return FALSE;
		}
		/**
		 * *******************************************************
		 * Set SSL configuration
		 * *******************************************************
		 */
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_USERAGENT,'casUA_wa67h28m_alliance');
		if($this->caCertPath)
		{
			curl_setopt($ch, CURLOPT_CAINFO, $this->caCertPath['pem']);
			curl_setopt($ch, CURLOPT_SSLCERT, $this->caCertPath['crt']);
			curl_setopt($ch, CURLOPT_SSLKEY, $this->caCertPath['key']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			phpCAS::trace('CURL: Set CURLOPT_CAINFO');
		}
		
		// return the CURL output into a variable
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		/**
		 * *******************************************************
		 * Perform the query
		 * *******************************************************
		 */
		$buf = curl_exec($ch);
		if($buf === false)
		{
			phpCAS::trace('curl_exec() failed');
			$this->storeErrorMessage('CURL error #' . curl_errno($ch) . ': ' . curl_error($ch));
			$res = false;
		}
		else
		{
			$this->storeResponseBody($buf);
			phpCAS::trace("Response Body: \n" . $buf . "\n");
			$res = true;
		}
		// close the CURL session
		curl_close($ch);
		
		phpCAS::traceEnd($res);
		return $res;
	}

	/**
	 * Store the response body.
	 *
	 * @param string $body body to store
	 *
	 * @return void
	 */
	private function storeResponseBody($body)
	{
		$this->_responseBody = $body;
	}

	/**
	 * Add a string to our error message.
	 *
	 * @param string $message message to add
	 *
	 * @return void
	 */
	private function storeErrorMessage($message)
	{
		$this->_errorMessage .= $message;
	}

	/**
	 * Answer the body of response.
	 *
	 * @return string
	 */
	public function getResponseBody()
	{
		return $this->_responseBody;
	}

	/**
	 * Answer a message describing any errors if the request failed.
	 *
	 * @return string
	 */
	public function getErrorMessage()
	{
		return $this->_errorMessage;
	}
}
